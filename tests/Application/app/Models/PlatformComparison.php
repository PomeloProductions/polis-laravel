<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Carbon;
use Polis\Contracts\Models\CanBeAggregatedContract;
use Polis\Models\BaseModelAbstract;

/**
 * Class PlatformComparison
 *
 * Harness-only base-table model for the multi-dimensional intersection-statistics
 * tests. One row records that an owner compared platform_a with platform_b within
 * a voting_type (a self-cross of the platform entity against itself — the shape
 * HSC's voting_pair_stats materializes).
 *
 * Implements CanBeAggregatedContract so the AggregatedModelObserver fires on
 * writes and the incremental tuple-delta maintenance path is exercised. It
 * declares NO legacy relation-traversal target path (returns []) — its cells are
 * produced by the GROUP BY engine, keyed off its own columns.
 *
 * @property int $id
 * @property int $owner_id
 * @property string $voting_type
 * @property int $platform_a_id
 * @property int $platform_b_id
 * @property int|null $comparison_group_id
 * @property Carbon|null $compared_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static \Database\Factories\PlatformComparisonFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class PlatformComparison extends BaseModelAbstract implements CanBeAggregatedContract
{
    protected $casts = [
        'compared_at' => 'datetime',
    ];

    /**
     * The engine-backed statistics resolve this row's cells directly from its
     * columns via the GROUP BY engine, so there is no legacy relation-traversal
     * target path. The empty array keeps the legacy observer path a no-op while
     * the incremental cell-maintenance path still runs.
     */
    public function getStatisticTargetRelationPath(): array
    {
        return [];
    }
}
