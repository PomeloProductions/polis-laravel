<?php

declare(strict_types=1);

namespace Polis\Support\Statistic;

use App\Models\Statistic\Statistic;

/**
 * Class DimensionTuple
 *
 * An immutable, canonical representation of the EXTRA axes of a single
 * multi-dimensional statistic cell. The primary axis (the morph target on
 * `target_statistics.target_type`/`target_id`) is NOT part of a DimensionTuple;
 * only the additional axes declared by {@see Statistic}'s
 * `dimensions` are.
 *
 * Its job is to produce a STABLE, deterministic `dimension_key` string so the
 * same logical combination of axis values always maps to exactly one
 * `target_statistics` row (and therefore one DB UNIQUE cell). Stability is
 * guaranteed by:
 *   - sorting the axes by name before hashing, so declaration order never
 *     changes the key;
 *   - normalizing each axis value to a scalar string token;
 *   - honoring per-axis canonical ORDERING so an unordered self-cross pair
 *     (platform_a, platform_b) maps (A,B) and (B,A) to the same key — this is
 *     what lets HSC's voting_pair_stats self-cross live on the primitive.
 *
 * An empty tuple canonicalizes to the empty string '', which is exactly the
 * back-compat key for the existing single-target statistics (no extra axes).
 */
final class DimensionTuple
{
    /**
     * @param  array<string, DimensionValue>  $values  Axis-name => resolved axis value.
     * @param  array<int, array<int, string>>  $pairGroups  Groups of axis names whose
     *                                                      values are canonicalized together as an
     *                                                      unordered set (sorted ascending), e.g.
     *                                                      [['platform_a', 'platform_b']].
     */
    public function __construct(
        private readonly array $values,
        private readonly array $pairGroups = [],
    ) {}

    /**
     * The axis-name => DimensionValue map, exactly as supplied (unsorted).
     *
     * @return array<string, DimensionValue>
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * Return a NEW tuple with the pair-grouped axes canonically ordered so an
     * unordered pair collapses to one representation. Within each declared
     * group the member axes are re-assigned their values in ascending sort
     * order, so (platform_a=B, platform_b=A) becomes (platform_a=A,
     * platform_b=B). Axes outside any group are untouched.
     */
    public function canonicalized(): self
    {
        if ($this->pairGroups === []) {
            return $this;
        }

        $values = $this->values;

        foreach ($this->pairGroups as $group) {
            // Only reorder when every member of the group is present.
            $present = array_values(array_filter(
                $group,
                static fn (string $axis): bool => isset($values[$axis]),
            ));

            if (count($present) !== count($group)) {
                continue;
            }

            $members = array_map(
                static fn (string $axis): DimensionValue => $values[$axis],
                $group,
            );

            usort(
                $members,
                static fn (DimensionValue $a, DimensionValue $b): int => $a->compareTo($b),
            );

            foreach ($group as $index => $axis) {
                $values[$axis] = $members[$index];
            }
        }

        return new self($values, $this->pairGroups);
    }

    /**
     * The canonical dimension_key: a deterministic, order-independent hash of
     * the (canonicalized) axis tokens. Returns '' for an empty tuple so
     * single-target statistics keep their back-compat key.
     */
    public function key(): string
    {
        if ($this->values === []) {
            return '';
        }

        $canonical = $this->canonicalized()->values;

        // Sort by axis name so declaration order never changes the key.
        ksort($canonical);

        $parts = [];
        foreach ($canonical as $axis => $value) {
            $parts[] = $axis.'='.$value->token();
        }

        // A readable, bounded, collision-resistant key. The joined form is kept
        // human-inspectable but hashed so the column stays a fixed width
        // regardless of how many axes a statistic declares.
        return hash('sha1', implode('&', $parts));
    }
}
