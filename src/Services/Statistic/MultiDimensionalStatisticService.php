<?php

declare(strict_types=1);

namespace Polis\Services\Statistic;

use App\Models\Statistic\Statistic;
use Polis\Contracts\Repositories\Statistic\TargetStatisticRepositoryContract;
use Polis\Support\Statistic\DimensionValue;

/**
 * Class MultiDimensionalStatisticService
 *
 * Orchestrates a FULL recompute of a multi-dimensional statistic: run the GROUP
 * BY engine, materialize each produced cell (sparse — only groups that exist),
 * write the normalized axis child rows, and prune cells that no longer exist.
 *
 * This is the recompute core the queued recount + incremental maintenance both
 * funnel into for engine-backed statistics. It deliberately does NOT enumerate
 * the cross-product; a cell exists only if its group produced rows.
 */
class MultiDimensionalStatisticService
{
    public function __construct(
        private readonly MultiDimensionalAggregationService $aggregationService,
        private readonly TargetStatisticRepositoryContract $targetStatisticRepository,
    ) {}

    /**
     * Recompute every cell of the statistic and return the dimension_keys that
     * were written (so a dependent derived recompute can align on them).
     *
     * @return array<int, string>
     */
    public function recompute(Statistic $statistic): array
    {
        $cells = $this->aggregationService->aggregate($statistic);
        $writtenKeys = [];

        foreach ($cells as $cell) {
            $this->persistCell($statistic, $cell);
            $writtenKeys[] = $cell->dimensionKey();
        }

        // Remove cells whose group vanished since the last recompute (sparse +
        // self-healing). Scoped to the whole statistic because a full recompute
        // reproduces every live cell across every target.
        $this->targetStatisticRepository->deleteStaleCells($statistic->id, $writtenKeys);

        return array_values(array_unique($writtenKeys));
    }

    /**
     * BOUNDED incremental recompute: re-aggregate only the cells belonging to a
     * single primary target (the one a changed row affects), and prune that
     * target's stale cells. This is the tuple-delta path §4 — a changed row's
     * affected cells are recomputed without touching any other target.
     *
     * @return array<int, string> The dimension_keys written for this target.
     */
    public function recomputeForTarget(
        Statistic $statistic,
        string $targetType,
        int|string $targetId,
    ): array {
        $cells = $this->aggregationService->aggregate($statistic, $targetId);
        $writtenKeys = [];

        foreach ($cells as $cell) {
            // Only persist cells that actually belong to this target scope.
            if ((string) $cell->targetId !== (string) $targetId) {
                continue;
            }

            $this->persistCell($statistic, $cell);
            $writtenKeys[] = $cell->dimensionKey();
        }

        $this->targetStatisticRepository->deleteStaleCells(
            $statistic->id,
            $writtenKeys,
            $targetType,
            $targetId,
        );

        return array_values(array_unique($writtenKeys));
    }

    /**
     * Materialize one cell: the scalar `value` (for fast reads + SUM rollups),
     * the full `{axis: value}` tuple in `result`, and the normalized child axis
     * rows.
     */
    private function persistCell(Statistic $statistic, AggregatedCell $cell): void
    {
        $axisRows = [];
        $resultTuple = [];

        foreach ($cell->dimensionTuple->values() as $axis => $value) {
            $resultTuple[$axis] = $this->axisResultValue($value);
            $axisRows[] = $this->axisChildRow($axis, $value);
        }

        $this->targetStatisticRepository->upsertCell(
            statisticId: $statistic->id,
            targetType: $cell->targetType,
            targetId: $cell->targetId,
            dimensionKey: $cell->dimensionKey(),
            values: [
                'value' => $cell->measureValue,
                'result' => [
                    'measure' => $cell->measureValue,
                    'tuple' => $resultTuple,
                ],
            ],
            dimensions: $axisRows,
        );
    }

    /**
     * The human-readable tuple value stored in `result.tuple` for an axis.
     *
     * @return array<string, mixed>
     */
    private function axisResultValue(DimensionValue $value): array
    {
        return match ($value->kind()) {
            DimensionValue::KIND_ENTITY => [
                'type' => $value->valueType(),
                'id' => $value->valueId(),
            ],
            default => ['value' => $value->valueScalar()],
        };
    }

    /**
     * The normalized child row (target_statistic_dimensions) for an axis value.
     *
     * @return array<string, mixed>
     */
    private function axisChildRow(string $axis, DimensionValue $value): array
    {
        if ($value->kind() === DimensionValue::KIND_ENTITY) {
            return [
                'axis' => $axis,
                'value_type' => $value->valueType(),
                'value_id' => $value->valueId(),
                'value_scalar' => null,
            ];
        }

        return [
            'axis' => $axis,
            'value_type' => null,
            'value_id' => null,
            'value_scalar' => $value->valueScalar(),
        ];
    }
}
