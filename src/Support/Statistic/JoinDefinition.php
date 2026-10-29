<?php

declare(strict_types=1);

namespace Polis\Support\Statistic;

use InvalidArgumentException;

/**
 * Class JoinDefinition
 *
 * One declarative join in a multi-dimensional statistic's `joins` list. It lets a
 * statistic COUNT one base table while its axes / filters / measure / primary
 * target reference RELATED tables — making the bespoke per-shape SQL VIEWS the
 * HSC consumer used to hand-roll unnecessary.
 *
 * Parsed from an entry in the statistic's `joins` json:
 *
 *   { "table": "release_dates", "alias": "rd",
 *     "left": "base.id", "right": "rd.release_id",
 *     "type": "inner",                // inner | left ; default inner
 *     "guard_soft_deletes": true,     // default true: exclude deleted_at rows
 *     "on_extra": [ ["ci.item_type", "=", "release"] ] }
 *
 * `left`/`right` are qualified `alias.column` references; `base` is the reserved
 * base alias. Joins apply IN ORDER, so a later join may reference an earlier
 * join's alias (release_dates → collection_items → collections).
 *
 * Soft-delete guard semantics (applied by the aggregation engine):
 *   - INNER join → `alias.deleted_at IS NULL` is added to the WHERE clause.
 *   - LEFT join  → it is added to the ON closure, so a LEFT join is NOT silently
 *     narrowed into an inner join by excluding unmatched (all-NULL) rows.
 *   - Auto-skipped when the joined table has no `deleted_at` column.
 *
 * SUB-SELECT (derived-table) joins: instead of `table` a join may carry a
 * `table_raw` — a raw SQL sub-select (WITHOUT the surrounding parens or alias)
 * that the engine wraps as `(<table_raw>) as <alias>` and joins to. This lets a
 * statistic derive a per-parent column (e.g. earliest_year = YEAR(MIN(
 * release_date)) GROUP BY release_id) and then group by it — the real HSC
 * "earliest year" shape. Exactly ONE of `table` / `table_raw` must be present;
 * `alias` is REQUIRED for a `table_raw` join. The sub-select owns its own
 * filtering (its own WHERE), so the engine applies NO automatic soft-delete
 * guard and does NO `deleted_at` column lookup against the alias.
 *
 *   { "table_raw": "SELECT release_id, YEAR(MIN(release_date)) AS earliest_year
 *                   FROM release_dates WHERE release_date IS NOT NULL
 *                   AND deleted_at IS NULL GROUP BY release_id",
 *     "alias": "ey", "left": "base.id", "right": "ey.release_id" }
 *
 * SECURITY: `table_raw` is RAW SQL spliced directly into the query. It is for
 * TRUSTED, system-SEEDED statistic configuration ONLY and must NEVER contain or
 * be built from user input — there is no escaping or parameterization of its
 * contents.
 */
final class JoinDefinition
{
    public const TYPE_INNER = 'inner';

    public const TYPE_LEFT = 'left';

    public const TYPES = [self::TYPE_INNER, self::TYPE_LEFT];

    /**
     * @param  array<int, array{0: string, 1: string, 2: mixed}>  $onExtra  Extra
     *                                                                      AND-ed ON conditions as [column, operator, literalValue] triples.
     */
    public function __construct(
        private readonly ?string $table,
        private readonly string $alias,
        private readonly string $left,
        private readonly string $right,
        private readonly string $type = self::TYPE_INNER,
        private readonly bool $guardSoftDeletes = true,
        private readonly array $onExtra = [],
        private readonly ?string $tableRaw = null,
    ) {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("Unsupported join type [{$type}].");
        }

        if (($table === null) === ($tableRaw === null)) {
            throw new InvalidArgumentException(
                'A join requires exactly one of `table` or `table_raw`.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public static function fromArray(array $definition): self
    {
        $hasTable = array_key_exists('table', $definition) && $definition['table'] !== null;
        $hasTableRaw = array_key_exists('table_raw', $definition) && $definition['table_raw'] !== null;

        if ($hasTable === $hasTableRaw) {
            throw new InvalidArgumentException(
                'A join requires exactly one of `table` or `table_raw`.'
            );
        }

        $table = $hasTable ? (string) $definition['table'] : null;
        $tableRaw = $hasTableRaw ? (string) $definition['table_raw'] : null;

        // A sub-select join has no physical table name to fall back to, so the
        // alias is required; a physical-table join defaults the alias to it.
        if ($hasTableRaw && ! array_key_exists('alias', $definition)) {
            throw new InvalidArgumentException('A `table_raw` join requires an alias.');
        }

        $alias = (string) ($definition['alias'] ?? $table);

        $type = strtolower((string) ($definition['type'] ?? self::TYPE_INNER));

        return new self(
            table: $table,
            alias: $alias,
            left: (string) ($definition['left'] ?? throw new InvalidArgumentException('A join requires a left column.')),
            right: (string) ($definition['right'] ?? throw new InvalidArgumentException('A join requires a right column.')),
            type: $type,
            guardSoftDeletes: array_key_exists('guard_soft_deletes', $definition)
                ? (bool) $definition['guard_soft_deletes']
                : true,
            onExtra: self::parseOnExtra($definition['on_extra'] ?? []),
            tableRaw: $tableRaw,
        );
    }

    /**
     * Normalize the optional `on_extra` into a list of [column, op, value]
     * triples, tolerating a single triple given un-nested.
     *
     * @return array<int, array{0: string, 1: string, 2: mixed}>
     */
    private static function parseOnExtra(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        // A single un-nested triple like ["ci.item_type", "=", "release"].
        if (array_is_list($raw) && count($raw) === 3 && ! is_array($raw[0])) {
            return [[(string) $raw[0], (string) $raw[1], $raw[2]]];
        }

        $conditions = [];

        foreach ($raw as $condition) {
            if (! is_array($condition) || count($condition) < 3) {
                throw new InvalidArgumentException('Each on_extra condition must be [column, operator, value].');
            }

            $condition = array_values($condition);
            $conditions[] = [(string) $condition[0], (string) $condition[1], $condition[2]];
        }

        return $conditions;
    }

    /**
     * The physical table name, or null for a `table_raw` sub-select join.
     */
    public function table(): ?string
    {
        return $this->table;
    }

    /**
     * The raw SQL sub-select (without surrounding parens/alias) for a sub-select
     * join, or null for a physical-table join.
     *
     * SECURITY: raw SQL — TRUSTED, system-seeded config only; never user input.
     */
    public function tableRaw(): ?string
    {
        return $this->tableRaw;
    }

    /**
     * Whether this join targets a raw sub-select (derived table) rather than a
     * physical table. Sub-select joins skip the automatic soft-delete guard and
     * the `deleted_at` column lookup — the sub-select owns its own filtering.
     */
    public function isSubSelect(): bool
    {
        return $this->tableRaw !== null;
    }

    public function alias(): string
    {
        return $this->alias;
    }

    /**
     * The fully-qualified table-and-alias token for the FROM/JOIN clause. Only
     * valid for a physical-table join; a sub-select join is wrapped as
     * `(table_raw) as alias` by the aggregation engine instead.
     */
    public function tableWithAlias(): string
    {
        return $this->table.' as '.$this->alias;
    }

    public function left(): string
    {
        return $this->left;
    }

    public function right(): string
    {
        return $this->right;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isLeft(): bool
    {
        return $this->type === self::TYPE_LEFT;
    }

    public function guardsSoftDeletes(): bool
    {
        return $this->guardSoftDeletes;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: mixed}>
     */
    public function onExtra(): array
    {
        return $this->onExtra;
    }
}
