<?php

declare(strict_types=1);

namespace Polis\Services\Statistic;

use Polis\Support\Statistic\DimensionTuple;

/**
 * Class AggregatedCell
 *
 * One output cell of the GROUP BY engine: a scalar measure value for a specific
 * primary-target + extra-axis combination. The dimension tuple is already
 * canonicalized (unordered self-cross pairs collapsed) so its key() is the
 * cell's dimension_key.
 */
final class AggregatedCell
{
    public function __construct(
        public readonly float $measureValue,
        public readonly ?string $targetType,
        public readonly int|string|null $targetId,
        public readonly DimensionTuple $dimensionTuple,
    ) {}

    /**
     * The canonical dimension_key for this cell.
     */
    public function dimensionKey(): string
    {
        return $this->dimensionTuple->key();
    }
}
