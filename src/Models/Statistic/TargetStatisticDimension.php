<?php

declare(strict_types=1);

namespace Polis\Models\Statistic;

use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TargetStatisticDimension
 *
 * One EXTRA axis value of a single multi-dimensional statistic cell
 * ({@see TargetStatistic}). The normalized child of the "option A" design: a
 * cell with N extra axes has N of these rows, each describing one axis's
 * resolved value so partial-tuple slices and single-axis rollups are
 * index-served without a wide pivot.
 *
 * An axis value is either an entity (value_type + value_id) or a scalar /
 * date-bucket (value_scalar). `statistic_id` is denormalized from the parent so
 * slice queries filter on (statistic_id, axis, value_*) directly.
 *
 * No soft-deletes: dimension rows are owned by their parent cell and cascade on
 * the parent's hard delete; a soft-deleted cell simply keeps its (unused) axis
 * rows.
 *
 * @property int $id
 * @property int $target_statistic_id
 * @property int $statistic_id
 * @property string $axis
 * @property string|null $value_type
 * @property int|null $value_id
 * @property string|null $value_scalar
 * @property-read TargetStatistic $targetStatistic
 */
class TargetStatisticDimension extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value_id' => 'integer',
    ];

    /**
     * The cell this axis value belongs to.
     */
    public function targetStatistic(): BelongsTo
    {
        return $this->belongsTo(TargetStatistic::class);
    }
}
