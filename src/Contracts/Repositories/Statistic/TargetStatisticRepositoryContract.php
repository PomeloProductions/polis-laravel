<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\Statistic;

use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Polis\Contracts\Models\CanBeStatisticTargetContract;
use Polis\Contracts\Repositories\BaseRepositoryContract;

/**
 * Interface TargetStatisticRepositoryContract
 */
interface TargetStatisticRepositoryContract extends BaseRepositoryContract
{
    /**
     * Creates a new target statistic model
     */
    public function createForTarget(CanBeStatisticTargetContract $target, array $data): TargetStatistic;

    /**
     * Find all statistics for a specific target
     */
    public function findAllForTarget(CanBeStatisticTargetContract $target): Collection;

    /**
     * Find a specific statistic for a target
     */
    public function findForTarget(CanBeStatisticTargetContract $target, int $statisticId): ?TargetStatistic;

    /**
     * Upsert a single multi-dimensional CELL, keyed on the DB-unique tuple
     * (statistic_id, target_type, target_id, dimension_key), and (re)write its
     * normalized axis child rows. Returns the persisted cell.
     *
     * @param  array<string, mixed>  $values  Columns to set on the cell (value, result).
     * @param  array<int, array<string, mixed>>  $dimensions  Child axis rows
     *                                                        (axis, value_type?, value_id?, value_scalar?).
     */
    public function upsertCell(
        int $statisticId,
        ?string $targetType,
        int|string|null $targetId,
        string $dimensionKey,
        array $values,
        array $dimensions = [],
    ): TargetStatistic;

    /**
     * Delete the cells of a statistic whose dimension_key is NOT in the given
     * keep-list — the stale cells left over after a full recompute. Scoped to a
     * single target when $targetType/$targetId are supplied. Returns the number
     * of cells removed.
     *
     * @param  array<int, string>  $keepKeys
     */
    public function deleteStaleCells(
        int $statisticId,
        array $keepKeys,
        ?string $targetType = null,
        int|string|null $targetId = null,
    ): int;

    /**
     * Read the scalar `value` of a statistic's cell for a dimension_key (scoped
     * to a target when supplied). Used by the derived recompute to fetch operand
     * values aligned by tuple. Null when the cell does not exist.
     */
    public function cellValue(
        int $statisticId,
        string $dimensionKey,
        ?string $targetType = null,
        int|string|null $targetId = null,
    ): ?float;
}
