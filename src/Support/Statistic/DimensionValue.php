<?php

declare(strict_types=1);

namespace Polis\Support\Statistic;

/**
 * Class DimensionValue
 *
 * A single resolved axis value within a {@see DimensionTuple}. An axis value is
 * one of three kinds, mirroring the stored shape on
 * `target_statistic_dimensions`:
 *   - entity:      a morph reference ({value_type}, {value_id}); its token is
 *                  "{type}#{id}".
 *   - scalar:      an enum / id / arbitrary string ({value_scalar}); its token
 *                  is the string itself.
 *   - date_bucket: a resolved bucket label (e.g. "2026-W05"); stored as a
 *                  scalar, its token is the label.
 *
 * The `token()` is the canonical string used by {@see DimensionTuple::key()};
 * `compareTo()` defines the stable ordering used when canonicalizing an
 * unordered self-cross pair.
 */
final class DimensionValue
{
    public const KIND_ENTITY = 'entity';

    public const KIND_SCALAR = 'scalar';

    public const KIND_DATE_BUCKET = 'date_bucket';

    private function __construct(
        private readonly string $kind,
        private readonly ?string $valueType,
        private readonly int|string|null $valueId,
        private readonly ?string $valueScalar,
    ) {}

    /**
     * An entity-kind axis value (a morph reference).
     */
    public static function entity(string $type, int|string $id): self
    {
        return new self(self::KIND_ENTITY, $type, $id, null);
    }

    /**
     * A scalar-kind axis value (enum / id / arbitrary string). A null scalar is
     * normalized to the empty string so a NULL axis value still produces a
     * stable, non-colliding token.
     */
    public static function scalar(int|string|null $value): self
    {
        return new self(
            self::KIND_SCALAR,
            null,
            null,
            $value === null ? '' : (string) $value,
        );
    }

    /**
     * A date-bucket axis value (a resolved bucket label, stored as a scalar).
     */
    public static function dateBucket(string $label): self
    {
        return new self(self::KIND_DATE_BUCKET, null, null, $label);
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function valueType(): ?string
    {
        return $this->valueType;
    }

    public function valueId(): int|string|null
    {
        return $this->valueId;
    }

    public function valueScalar(): ?string
    {
        return $this->valueScalar;
    }

    /**
     * The canonical token used in the dimension_key hash. Prefixed by kind so an
     * entity "7" and a scalar "7" can never collide.
     */
    public function token(): string
    {
        return match ($this->kind) {
            self::KIND_ENTITY => 'e:'.$this->valueType.'#'.$this->valueId,
            self::KIND_DATE_BUCKET => 'd:'.(string) $this->valueScalar,
            default => 's:'.(string) $this->valueScalar,
        };
    }

    /**
     * Stable ordering for canonicalizing unordered pairs. Entity values order by
     * (type, numeric-id-aware) and scalars order naturally; the token is a
     * deterministic tie-breaker so the comparison is total.
     */
    public function compareTo(self $other): int
    {
        if ($this->kind === self::KIND_ENTITY && $other->kind === self::KIND_ENTITY) {
            $typeCmp = strcmp((string) $this->valueType, (string) $other->valueType);

            if ($typeCmp !== 0) {
                return $typeCmp;
            }

            return $this->compareIds($this->valueId, $other->valueId);
        }

        return strcmp($this->token(), $other->token());
    }

    /**
     * Compare two ids numerically when both are numeric (so 2 < 10), otherwise
     * lexically.
     */
    private function compareIds(int|string|null $a, int|string|null $b): int
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (int) $a <=> (int) $b;
        }

        return strcmp((string) $a, (string) $b);
    }
}
