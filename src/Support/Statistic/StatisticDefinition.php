<?php

declare(strict_types=1);

namespace Polis\Support\Statistic;

use Illuminate\Database\Eloquent\Model;

/**
 * Class StatisticDefinition
 *
 * A read-only facade over a Statistic model's multi-dimensional configuration:
 * its `kind`, parsed `dimensions` (extra axes), `measure`, and — for derived
 * statistics — the derived expression + declared operands.
 *
 * A statistic OPTS IN to the new GROUP BY engine by declaring `dimensions` and a
 * `measure`. Statistics that declare neither are "legacy" and keep running
 * through the unchanged in-PHP relation-traversal path (back-compat), so
 * existing single-target and `unique`-filter statistics are untouched.
 *
 * The facade reads raw attributes so it works against both the package base
 * model and any consumer App\ subclass without a hard type dependency.
 */
final class StatisticDefinition
{
    public const KIND_COUNT = 'count';

    public const KIND_DERIVED = 'derived';

    public function __construct(private readonly Model $statistic) {}

    public static function for(Model $statistic): self
    {
        return new self($statistic);
    }

    /**
     * The statistic kind. Defaults to `count` so existing rows (null kind) read
     * as ordinary base statistics.
     */
    public function kind(): string
    {
        $kind = $this->statistic->getAttribute('kind');

        return $kind === null || $kind === '' ? self::KIND_COUNT : (string) $kind;
    }

    public function isDerived(): bool
    {
        return $this->kind() === self::KIND_DERIVED;
    }

    /**
     * True when this statistic uses the multi-dimensional GROUP BY engine, i.e.
     * it declares at least one dimension AND a measure. Otherwise the legacy
     * in-PHP path handles it.
     */
    public function usesAggregationEngine(): bool
    {
        return ! $this->isDerived()
            && $this->rawMeasure() !== null
            && $this->dimensions() !== [];
    }

    /**
     * The parsed declarative joins, in declaration order. A join lets the
     * statistic count one base table while axes / filters / measure / primary
     * target reference RELATED tables. No `joins` declared ⇒ empty array ⇒ the
     * engine behaves exactly as before (back-compat).
     *
     * @return array<int, JoinDefinition>
     */
    public function joins(): array
    {
        $raw = $this->decode($this->statistic->getAttribute('joins'));

        return array_map(
            static fn (array $join): JoinDefinition => JoinDefinition::fromArray($join),
            array_values($raw),
        );
    }

    /**
     * The parsed extra axes, in declaration order.
     *
     * @return array<int, DimensionDefinition>
     */
    public function dimensions(): array
    {
        $raw = $this->decode($this->statistic->getAttribute('dimensions'));

        return array_map(
            static fn (array $axis): DimensionDefinition => DimensionDefinition::fromArray($axis),
            array_values($raw),
        );
    }

    /**
     * The parsed measure, or a default COUNT when none is declared (so the
     * engine always has a scalar aggregate to compute).
     */
    public function measure(): MeasureDefinition
    {
        $raw = $this->rawMeasure();

        return MeasureDefinition::fromArray($raw ?? ['agg' => MeasureDefinition::COUNT]);
    }

    /**
     * The PRIMARY-axis binding for the GROUP BY engine: which base-table column
     * carries the target id, and the morph alias to stamp as target_type. Read
     * from the measure's `target` config:
     *
     *   "measure": { "agg": "count",
     *                "target": { "type": "user", "id_column": "owner_id" } }
     *
     * Returns [type, idColumn] or null when the statistic has no primary-axis
     * binding (a single global cell keyed only by the extra axes).
     *
     * @return array{type: string, id_column: string}|null
     */
    public function primaryTarget(): ?array
    {
        $measure = $this->rawMeasure();
        $target = $measure['target'] ?? null;

        if (! is_array($target) || ! isset($target['type'], $target['id_column'])) {
            return null;
        }

        return [
            'type' => (string) $target['type'],
            'id_column' => (string) $target['id_column'],
        ];
    }

    /**
     * The derived operation (ratio|difference|sum) for a derived statistic.
     */
    public function derivedOperation(): ?string
    {
        $definition = $this->decode($this->statistic->getAttribute('derived_definition'));

        return isset($definition['operation']) ? (string) $definition['operation'] : null;
    }

    /**
     * The ordered operand statistic ids a derived statistic depends on. Reads
     * from the parsed `derived_definition.operands` so the expression evaluator
     * receives operand values in the declared order.
     *
     * @return array<int, int>
     */
    public function derivedOperandIds(): array
    {
        $definition = $this->decode($this->statistic->getAttribute('derived_definition'));
        $operands = $definition['operands'] ?? [];

        return array_map(static fn ($id): int => (int) $id, array_values($operands));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rawMeasure(): ?array
    {
        $decoded = $this->decode($this->statistic->getAttribute('measure'));

        return $decoded === [] ? null : $decoded;
    }

    /**
     * Normalize a json/array/null attribute into an array.
     *
     * @return array<array-key, mixed>
     */
    private function decode(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
