<?php

declare(strict_types=1);

namespace Polis\Support\Statistic;

use InvalidArgumentException;

/**
 * Class DimensionDefinition
 *
 * One EXTRA axis of a multi-dimensional statistic (the primary axis stays the
 * morph target). Parsed from an entry in the statistic's `dimensions` json.
 *
 * Shapes:
 *   // a scalar/enum/id column on the base table
 *   { "name": "voting_type", "kind": "scalar", "column": "voting_type" }
 *
 *   // an entity reference (morph): a {type, id} pair. `type` may be a literal
 *   // morph alias, or resolved from a column via "type_column".
 *   { "name": "platform_a", "kind": "entity", "id_column": "platform_a_id",
 *     "type": "platform" }
 *
 *   // a time axis bucketed in a timezone with a configurable week start
 *   { "name": "day", "kind": "date_bucket", "column": "created_at",
 *     "granularity": "day", "timezone": "America/New_York", "week_start": 0 }
 *
 * `pair_group` (optional string) tags two axes that form an UNORDERED pair:
 * axes sharing the same pair_group token are canonicalized together so (A,B)
 * and (B,A) map to one cell — the self-cross shape (platform_a ≤ platform_b).
 */
final class DimensionDefinition
{
    public const KIND_SCALAR = 'scalar';

    public const KIND_ENTITY = 'entity';

    public const KIND_DATE_BUCKET = 'date_bucket';

    public const KINDS = [self::KIND_SCALAR, self::KIND_ENTITY, self::KIND_DATE_BUCKET];

    /**
     * @param  array<string, mixed>  $config  The raw axis config (kept for
     *                                        date_bucket granularity/timezone/week_start
     *                                        and entity type/type_column access).
     */
    public function __construct(
        private readonly string $name,
        private readonly string $kind,
        private readonly ?string $column,
        private readonly array $config = [],
    ) {
        if (! in_array($kind, self::KINDS, true)) {
            throw new InvalidArgumentException("Unsupported dimension kind [{$kind}].");
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public static function fromArray(array $definition): self
    {
        $kind = (string) ($definition['kind'] ?? self::KIND_SCALAR);

        // entity axes resolve their id from id_column; scalar/date_bucket from column.
        $column = $kind === self::KIND_ENTITY
            ? ($definition['id_column'] ?? $definition['column'] ?? null)
            : ($definition['column'] ?? null);

        return new self(
            (string) ($definition['name'] ?? throw new InvalidArgumentException('A dimension requires a name.')),
            $kind,
            $column !== null ? (string) $column : null,
            $definition,
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function kind(): string
    {
        return $this->kind;
    }

    /**
     * The base-table column that carries this axis value (the id column for an
     * entity axis, the value column for scalar/date_bucket).
     */
    public function column(): ?string
    {
        return $this->column;
    }

    /**
     * The morph alias for an entity axis — either the literal `type` or resolved
     * later from `type_column`. Null for non-entity axes.
     */
    public function entityType(): ?string
    {
        return isset($this->config['type']) ? (string) $this->config['type'] : null;
    }

    public function entityTypeColumn(): ?string
    {
        return isset($this->config['type_column']) ? (string) $this->config['type_column'] : null;
    }

    public function granularity(): string
    {
        return (string) ($this->config['granularity'] ?? DateBucketResolver::DAY);
    }

    public function timezone(): string
    {
        return (string) ($this->config['timezone'] ?? 'UTC');
    }

    public function weekStart(): int
    {
        return (int) ($this->config['week_start'] ?? 0);
    }

    /**
     * The pair-group token this axis belongs to, if any (for unordered
     * self-cross pairs). Null when the axis is not paired.
     */
    public function pairGroup(): ?string
    {
        return isset($this->config['pair_group']) ? (string) $this->config['pair_group'] : null;
    }

    public function isPaired(): bool
    {
        return $this->pairGroup() !== null;
    }
}
