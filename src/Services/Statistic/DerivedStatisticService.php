<?php

declare(strict_types=1);

namespace Polis\Services\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\Statistic\StatisticDependency;
use App\Models\Statistic\TargetStatistic;
use InvalidArgumentException;
use Polis\Contracts\Repositories\Statistic\TargetStatisticRepositoryContract;
use Polis\Support\Statistic\DerivedExpressionEvaluator;
use Polis\Support\Statistic\StatisticDefinition;
use RuntimeException;

/**
 * Class DerivedStatisticService
 *
 * The separable DERIVED-statistic layer. A derived statistic is MATERIALIZED and
 * recomputed eagerly (push) whenever an operand it depends on is (re)computed —
 * reads return a stored value; correctness comes from the full recompute, never
 * from lazy read-time evaluation.
 *
 * A derived cell is computed from the operand statistics' MATCHING cells (same
 * target + dimension_key) via {@see DerivedExpressionEvaluator}
 * (ratio/difference/sum, zero-denominator- and missing-operand-safe). The
 * derived cell carries the same dimension-tuple key as its operands, so a
 * "coverage %" exists per cell, not only globally.
 *
 * CYCLE RULE (v1): derived-on-derived is FORBIDDEN. An operand must be a base
 * (non-derived) statistic; this is validated at recompute time and keeps the
 * recompute a single, terminating pass (no topological ordering needed).
 *
 * NON-ADDITIVE CAVEAT: a stored derived ratio/avg cell must NEVER be summed or
 * rolled up across a dimension. A rolled-up ratio must be re-derived from
 * rolled-up operands. Materializing the point value is fine; aggregating the
 * materialized ratio is not.
 *
 * The whole layer is self-contained: remove this service + the
 * RecomputeDerivedStatisticJob + the statistic_dependencies table and the core
 * engine is untouched.
 */
class DerivedStatisticService
{
    public function __construct(
        private readonly TargetStatisticRepositoryContract $targetStatisticRepository,
        private readonly DerivedExpressionEvaluator $evaluator,
    ) {}

    /**
     * Persist (or re-read) the declared dependency edges of a derived statistic
     * from its parsed definition, so the recompute trigger can resolve
     * dependents. Idempotent: existing edges are reconciled to the declaration.
     */
    public function syncDependencies(Statistic $statistic): void
    {
        $definition = StatisticDefinition::for($statistic);

        if (! $definition->isDerived()) {
            return;
        }

        StatisticDependency::query()->where('statistic_id', $statistic->id)->delete();

        foreach ($definition->derivedOperandIds() as $position => $operandId) {
            StatisticDependency::query()->create([
                'statistic_id' => $statistic->id,
                'depends_on_statistic_id' => $operandId,
                'position' => $position,
            ]);
        }
    }

    /**
     * FULL recompute of every cell of a derived statistic from the current
     * operand cells. Writes value/result per cell; prunes derived cells whose
     * aligning operand cells no longer exist.
     */
    public function recompute(Statistic $statistic): void
    {
        $definition = StatisticDefinition::for($statistic);

        if (! $definition->isDerived()) {
            throw new RuntimeException(
                "Statistic [{$statistic->id}] is not a derived statistic."
            );
        }

        $operation = $definition->derivedOperation();

        if ($operation === null) {
            throw new InvalidArgumentException(
                "Derived statistic [{$statistic->id}] declares no operation."
            );
        }

        $operandIds = $definition->derivedOperandIds();
        $this->guardAgainstDerivedOperands($operandIds);

        // The universe of cells to produce is the UNION of operand cells, keyed
        // by (target_type, target_id, dimension_key). A derived cell exists only
        // where at least one operand cell exists (sparse).
        $cellKeys = $this->operandCellKeys($operandIds);
        $writtenKeys = [];

        foreach ($cellKeys as $key => $coords) {
            [$targetType, $targetId, $dimensionKey] = $coords;

            $operands = array_map(
                fn (int $operandId): ?float => $this->targetStatisticRepository->cellValue(
                    $operandId,
                    $dimensionKey,
                    $targetType,
                    $targetId,
                ),
                $operandIds,
            );

            $value = $this->evaluator->evaluate($operation, $operands);

            $this->targetStatisticRepository->upsertCell(
                statisticId: $statistic->id,
                targetType: $targetType,
                targetId: $targetId,
                dimensionKey: $dimensionKey,
                values: [
                    // value is non-nullable float; store 0 but mark null-ness in
                    // result so a zero-denominator cell is distinguishable.
                    'value' => $value ?? 0.0,
                    'result' => [
                        'derived' => true,
                        'operation' => $operation,
                        'value' => $value,
                        'operands' => $operands,
                    ],
                ],
            );

            $writtenKeys[$targetType.'|'.$targetId][] = $dimensionKey;
        }

        $this->pruneStaleDerivedCells($statistic, $writtenKeys);
    }

    /**
     * Enqueue/recompute every derived statistic that depends on the given base
     * statistic. Called at the END of the base recompute path, once operands are
     * settled.
     */
    public function recomputeDependentsOf(int $baseStatisticId): void
    {
        $dependentIds = StatisticDependency::query()
            ->where('depends_on_statistic_id', $baseStatisticId)
            ->pluck('statistic_id')
            ->unique();

        foreach ($dependentIds as $dependentId) {
            $derived = Statistic::query()->find($dependentId);

            if ($derived !== null) {
                $this->recompute($derived);
            }
        }
    }

    /**
     * Enumerate the distinct (target_type, target_id, dimension_key) coordinates
     * present across all operand statistics' cells.
     *
     * @param  array<int, int>  $operandIds
     * @return array<string, array{0: ?string, 1: int|string|null, 2: string}>
     */
    private function operandCellKeys(array $operandIds): array
    {
        $keys = [];

        TargetStatistic::query()
            ->whereIn('statistic_id', $operandIds)
            ->select(['target_type', 'target_id', 'dimension_key'])
            ->distinct()
            ->get()
            ->each(function (TargetStatistic $cell) use (&$keys): void {
                $keys[$cell->target_type.'|'.$cell->target_id.'|'.$cell->dimension_key] = [
                    $cell->target_type,
                    $cell->target_id,
                    $cell->dimension_key,
                ];
            });

        return $keys;
    }

    /**
     * Forbid derived-on-derived operands (v1). Keeps recompute a single
     * terminating pass.
     *
     * @param  array<int, int>  $operandIds
     */
    private function guardAgainstDerivedOperands(array $operandIds): void
    {
        $derivedOperand = Statistic::query()
            ->whereIn('id', $operandIds)
            ->where('kind', StatisticDefinition::KIND_DERIVED)
            ->first();

        if ($derivedOperand !== null) {
            throw new RuntimeException(
                'Derived-on-derived is not supported in v1: operand statistic '
                ."[{$derivedOperand->id}] is itself derived."
            );
        }
    }

    /**
     * Prune derived cells whose aligning operand cells no longer exist.
     *
     * @param  array<string, array<int, string>>  $writtenKeys  "type|id" => keys[]
     */
    private function pruneStaleDerivedCells(Statistic $statistic, array $writtenKeys): void
    {
        // Collect every (target) scope we touched; within each, keep only the
        // keys we wrote. A scope with no written cells has all its derived cells
        // removed (its operands vanished).
        $touchedScopes = TargetStatistic::query()
            ->where('statistic_id', $statistic->id)
            ->select(['target_type', 'target_id'])
            ->distinct()
            ->get();

        foreach ($touchedScopes as $scope) {
            $scopeKey = $scope->target_type.'|'.$scope->target_id;
            $keep = $writtenKeys[$scopeKey] ?? [];

            $this->targetStatisticRepository->deleteStaleCells(
                $statistic->id,
                $keep,
                $scope->target_type,
                $scope->target_id,
            );
        }
    }
}
