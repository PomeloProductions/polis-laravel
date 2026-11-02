<?php

declare(strict_types=1);

namespace Polis\Repositories\Statistic;

use App\Models\Statistic\TargetStatistic;
use App\Models\Statistic\TargetStatisticDimension;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Polis\Contracts\Models\CanBeStatisticTargetContract;
use Polis\Contracts\Repositories\Statistic\TargetStatisticRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TargetStatisticRepository
 */
class TargetStatisticRepository extends BaseRepositoryAbstract implements TargetStatisticRepositoryContract
{
    /**
     * TargetStatisticRepository constructor.
     */
    public function __construct(
        TargetStatistic $model,
        LogContract $log
    ) {
        parent::__construct($model, $log);
    }

    /**
     * Creates a new target statistic model
     *
     * @param  CanBeStatisticTargetContract  $target  The target model to create statistics for
     * @param  array<string, mixed>  $data  The data to create the statistic with
     * @return TargetStatistic The newly created target statistic
     */
    public function createForTarget(CanBeStatisticTargetContract $target, array $data): TargetStatistic
    {
        $data['target_id'] = $target->id;
        $data['target_type'] = $target->morphRelationName();

        return $this->create($data);
    }

    /**
     * Find all statistics for a specific target
     *
     * @param  CanBeStatisticTargetContract  $target  The target model to find statistics for
     * @return Collection<int, TargetStatistic> Collection of target statistics
     */
    public function findAllForTarget(CanBeStatisticTargetContract $target): Collection
    {
        return $this->model
            ->where('target_type', $target->morphRelationName())
            ->where('target_id', $target->id)
            ->get();
    }

    /**
     * Find a specific statistic for a target
     *
     * @param  CanBeStatisticTargetContract  $target  The target model to find the statistic for
     * @param  int  $statisticId  The ID of the statistic to find
     * @return TargetStatistic|null The found target statistic or null if not found
     */
    public function findForTarget(CanBeStatisticTargetContract $target, int $statisticId): ?TargetStatistic
    {
        return $this->model
            ->where('target_type', $target->morphRelationName())
            ->where('target_id', $target->id)
            ->where('statistic_id', $statisticId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function upsertCell(
        int $statisticId,
        ?string $targetType,
        int|string|null $targetId,
        string $dimensionKey,
        array $values,
        array $dimensions = [],
    ): TargetStatistic {
        return DB::transaction(function () use (
            $statisticId,
            $targetType,
            $targetId,
            $dimensionKey,
            $values,
            $dimensions,
        ): TargetStatistic {
            // Resolve the cell on its DB-unique tuple INCLUDING soft-deleted rows.
            // The uniqueness index counts soft-deleted rows, so a trashed-blind
            // updateOrCreate — which only sees live rows — would INSERT when a
            // previously-stale group reappears and collide with the soft-deleted
            // zombie that still holds the tuple (SQLSTATE 1062). Looking up
            // withTrashed() lets us restore + update the existing row instead, so
            // the cell keeps a stable id and the write is idempotent regardless of
            // how the cell was soft-deleted. This is the dup-key-500 fix.
            /** @var TargetStatistic|null $cell */
            $cell = $this->model->newQuery()
                ->withTrashed()
                ->where('statistic_id', $statisticId)
                ->where('target_type', $targetType)
                ->where('target_id', $targetId)
                ->where('dimension_key', $dimensionKey)
                ->first();

            if ($cell === null) {
                /** @var TargetStatistic $cell */
                $cell = $this->model->newQuery()->create(array_merge([
                    'statistic_id' => $statisticId,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'dimension_key' => $dimensionKey,
                ], $values));
            } else {
                // A reappearing group: revive the soft-deleted cell and refresh it.
                if ($cell->trashed()) {
                    $cell->restore();
                }

                $cell->fill($values)->save();
            }

            // Rewrite the normalized axis child rows for this cell.
            $cell->dimensions()->delete();

            if ($dimensions !== []) {
                $now = now();
                $rows = array_map(static fn (array $axis): array => array_merge([
                    'target_statistic_id' => $cell->id,
                    'statistic_id' => $statisticId,
                    'value_type' => null,
                    'value_id' => null,
                    'value_scalar' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $axis), $dimensions);

                TargetStatisticDimension::query()->insert($rows);
            }

            return $cell;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function deleteStaleCells(
        int $statisticId,
        array $keepKeys,
        ?string $targetType = null,
        int|string|null $targetId = null,
    ): int {
        $query = $this->model->newQuery()
            ->where('statistic_id', $statisticId)
            ->whereNotIn('dimension_key', $keepKeys === [] ? [''] : $keepKeys);

        if ($targetType !== null) {
            $query->where('target_type', $targetType);
        }

        if ($targetId !== null) {
            $query->where('target_id', $targetId);
        }

        $stale = $query->get();

        foreach ($stale as $cell) {
            // Hard-delete the child axis rows so slice reads stay clean. (They
            // cascade with the forceDelete below too, but removing them explicitly
            // keeps the intent obvious.)
            $cell->dimensions()->delete();
        }

        // FORCE (hard) delete the stale cells rather than soft-deleting them. A
        // stale cell is a GROUP BY group that no longer produces rows — there is
        // no value to retain. Soft-deleting instead left a "zombie" row that still
        // occupied the (statistic_id, target_type, target_id, dimension_key)
        // unique index; if that group ever reappeared, the index rejected the new
        // insert with SQLSTATE 1062. Hard-deleting removes the collision source at
        // the root while keeping the delete scoped to this statistic (and target,
        // when supplied) so no unrelated rows are touched. upsertCell's
        // restore-on-reappear still covers any cell soft-deleted by other paths.
        return $query->forceDelete();
    }

    /**
     * {@inheritDoc}
     */
    public function cellValue(
        int $statisticId,
        string $dimensionKey,
        ?string $targetType = null,
        int|string|null $targetId = null,
    ): ?float {
        $query = $this->model->newQuery()
            ->where('statistic_id', $statisticId)
            ->where('dimension_key', $dimensionKey);

        if ($targetType !== null) {
            $query->where('target_type', $targetType);
        }

        if ($targetId !== null) {
            $query->where('target_id', $targetId);
        }

        $cell = $query->first();

        return $cell === null ? null : (float) $cell->value;
    }
}
