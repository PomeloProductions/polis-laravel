<?php

declare(strict_types=1);

namespace Polis\Models\Statistic;

use App\Models\Statistic\Statistic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class StatisticDependency
 *
 * A declared operand edge of a DERIVED statistic: `statistic_id` (the derived
 * statistic) depends on `depends_on_statistic_id` (a base operand) at the given
 * `position` (operand order). Persisting the edge lets the recompute trigger
 * resolve, cheaply, every derived statistic that must be recomputed when a base
 * statistic's cells change.
 *
 * Part of the separable derived layer; no soft-deletes.
 *
 * @property int $id
 * @property int $statistic_id
 * @property int $depends_on_statistic_id
 * @property int $position
 * @property-read Statistic $statistic
 * @property-read Statistic $dependsOn
 */
class StatisticDependency extends Model
{
    protected $guarded = [];

    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * The derived statistic that owns this dependency edge.
     */
    public function statistic(): BelongsTo
    {
        return $this->belongsTo(Statistic::class, 'statistic_id');
    }

    /**
     * The base (operand) statistic depended on.
     */
    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(Statistic::class, 'depends_on_statistic_id');
    }
}
