<?php

declare(strict_types=1);

namespace Polis\Services\Statistic;

use App\Models\Statistic\Statistic;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Polis\Jobs\Statistic\RecomputeDerivedStatisticJob;
use Polis\Support\Statistic\StatisticDefinition;

/**
 * Class TupleDeltaMaintenanceService
 *
 * Incremental maintenance for engine-backed (multi-dimensional) statistics. When
 * a base-table row changes, only the CELLS that row can affect need recomputing
 * — specifically the cells of the row's PRIMARY TARGET (its owner). Rather than
 * recomputing the entire statistic (every target), this re-runs a BOUNDED GROUP
 * BY scoped to that one primary target.
 *
 * For an update, BOTH the old and new primary-target scopes are recomputed (so a
 * row that moved owners settles both sides). Where a precise per-cell delta is
 * hard, a bounded per-target full recompute is the accepted strategy (spec §4).
 *
 * After the base cells settle it triggers the dependent derived recomputes, so
 * materialized ratios/sums stay correct on every write — this is what lets the
 * voting-pair self-cross live incrementally on the primitive.
 */
class TupleDeltaMaintenanceService
{
    public function __construct(
        private readonly MultiDimensionalStatisticService $multiDimensionalService,
        private readonly Dispatcher $dispatcher,
    ) {}

    /**
     * Recompute the affected cells for every engine-backed statistic whose base
     * table is the changed model's table. $previousTargetIds carries the row's
     * pre-update primary-target id(s) so a moved row settles its old scope too.
     *
     * @param  array<string, int|string|null>  $previousTargetIds  statistic-id-keyed
     *                                                             previous primary-target id for updates (optional).
     */
    public function maintainForChangedRow(Model $changed, array $previousTargetIds = []): void
    {
        foreach ($this->engineBackedStatisticsFor($changed) as $statistic) {
            $definition = StatisticDefinition::for($statistic);
            $primaryTarget = $definition->primaryTarget();

            if ($primaryTarget === null) {
                // No primary-axis binding: a single global-scope statistic; fall
                // back to a full recompute (still bounded by its cardinality).
                $this->multiDimensionalService->recompute($statistic);
                $this->triggerDerived($statistic);

                continue;
            }

            $scopes = $this->affectedScopes($changed, $statistic, $primaryTarget, $previousTargetIds);

            foreach ($scopes as $targetId) {
                $this->multiDimensionalService->recomputeForTarget(
                    $statistic,
                    $primaryTarget['type'],
                    $targetId,
                );
            }

            $this->triggerDerived($statistic);
        }
    }

    /**
     * The set of primary-target ids whose cells this change touches: the row's
     * current owner plus (for an update that moved owners) its previous owner.
     *
     * @param  array{type: string, id_column: string}  $primaryTarget
     * @param  array<string, int|string|null>  $previousTargetIds
     * @return array<int, int|string>
     */
    private function affectedScopes(
        Model $changed,
        Statistic $statistic,
        array $primaryTarget,
        array $previousTargetIds,
    ): array {
        $scopes = [];

        $current = $changed->getAttribute($primaryTarget['id_column']);
        if ($current !== null) {
            $scopes[(string) $current] = $current;
        }

        $previous = $previousTargetIds[(string) $statistic->id] ?? null;
        if ($previous !== null) {
            $scopes[(string) $previous] = $previous;
        }

        return array_values($scopes);
    }

    /**
     * Engine-backed statistics whose base `model` morph resolves to the changed
     * row's class.
     *
     * @return iterable<int, Statistic>
     */
    private function engineBackedStatisticsFor(Model $changed): iterable
    {
        foreach (Statistic::query()->whereNotNull('measure')->get() as $statistic) {
            $definition = StatisticDefinition::for($statistic);

            if (! $definition->usesAggregationEngine()) {
                continue;
            }

            $modelClass = Relation::getMorphedModel($statistic->model);

            if ($modelClass !== null && $changed instanceof $modelClass) {
                yield $statistic;
            }
        }
    }

    private function triggerDerived(Statistic $statistic): void
    {
        $this->dispatcher->dispatch(new RecomputeDerivedStatisticJob($statistic->id));
    }
}
