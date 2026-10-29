<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Carbon;
use Polis\Contracts\Models\CanBeAggregatedContract;
use Polis\Models\BaseModelAbstract;

/**
 * Class Release
 *
 * Harness-only base-table model for the DECLARATIVE JOINS tests — the HSC
 * "releases" shape. A release has NO date column of its own; its dates live in a
 * one-to-many `release_dates` table (regional release dates). This is the exact
 * shape that drove the join refinement: count releases, bucket a year axis off a
 * JOINED release_dates.release_date, and keep "Total Releases" correct via
 * count_distinct over the fan-out.
 *
 * @property int $id
 * @property int|null $game_id
 * @property int $platform_id
 * @property int|null $score
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static \Database\Factories\ReleaseFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class Release extends BaseModelAbstract implements CanBeAggregatedContract
{
    /**
     * Cells are produced by the GROUP BY engine from this row's columns (and its
     * declared joins), so there is no legacy relation-traversal target path.
     */
    public function getStatisticTargetRelationPath(): array
    {
        return [];
    }
}
