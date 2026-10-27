<?php

declare(strict_types=1);

namespace Polis\Support\Statistic;

use InvalidArgumentException;

/**
 * Class MeasureDefinition
 *
 * The scalar aggregate a multi-dimensional statistic computes per cell. Parsed
 * from the statistic's `measure` json:
 *
 *   { "agg": "count" }
 *   { "agg": "count_distinct", "column": "collection_item_id" }
 *   { "agg": "sum", "column": "result" }
 *   { "agg": "max", "column": "score" }
 *
 * `count` needs no column. `count_distinct` carries the "count-once" grain so
 * fan-out joins don't inflate (e.g. COUNT(DISTINCT collection_item_id)). `sum`
 * and `max` require a column/expression to aggregate.
 */
final class MeasureDefinition
{
    public const COUNT = 'count';

    public const COUNT_DISTINCT = 'count_distinct';

    public const SUM = 'sum';

    public const MAX = 'max';

    public const AGGREGATES = [self::COUNT, self::COUNT_DISTINCT, self::SUM, self::MAX];

    public function __construct(
        private readonly string $aggregate,
        private readonly ?string $column = null,
    ) {
        if (! in_array($aggregate, self::AGGREGATES, true)) {
            throw new InvalidArgumentException("Unsupported measure aggregate [{$aggregate}].");
        }

        if ($aggregate !== self::COUNT && ($column === null || $column === '')) {
            throw new InvalidArgumentException(
                "Measure aggregate [{$aggregate}] requires a column."
            );
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public static function fromArray(array $definition): self
    {
        return new self(
            (string) ($definition['agg'] ?? self::COUNT),
            isset($definition['column']) ? (string) $definition['column'] : null,
        );
    }

    public function aggregate(): string
    {
        return $this->aggregate;
    }

    public function column(): ?string
    {
        return $this->column;
    }

    /**
     * The SQL aggregate expression for this measure over the given base table
     * alias. Qualifying with the alias keeps the expression unambiguous once the
     * axis-source joins are added.
     */
    public function toSqlExpression(string $baseAlias): string
    {
        return match ($this->aggregate) {
            self::COUNT => 'COUNT(*)',
            self::COUNT_DISTINCT => 'COUNT(DISTINCT '.$this->qualify($baseAlias).')',
            self::SUM => 'SUM('.$this->qualify($baseAlias).')',
            self::MAX => 'MAX('.$this->qualify($baseAlias).')',
        };
    }

    /**
     * Qualify a bare column with the base alias; leave an already-qualified
     * column (containing a dot) untouched so a consumer can aggregate a joined
     * column when needed.
     */
    private function qualify(string $baseAlias): string
    {
        $column = (string) $this->column;

        if (str_contains($column, '.')) {
            return $column;
        }

        return $baseAlias.'.'.$column;
    }
}
