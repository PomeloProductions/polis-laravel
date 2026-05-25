<?php

declare(strict_types=1);

namespace Polis\Models\Statistic;

use App\Models\Statistic\Statistic;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Polis\Models\BaseModelAbstract;

/**
 * Class TargetStatistic
 *
 * @property int $id
 * @property int $target_id
 * @property string $target_type
 * @property int $statistic_id
 * @property float $value
 * @property array|null $filters
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Statistic $statistic
 * @property-read Model $target
 * @property array<array-key, mixed>|null $result
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetStatistic onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereFilters($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereResult($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereStatisticId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereTargetId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereTargetType($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereUpdatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|TargetStatistic whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetStatistic withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetStatistic withoutTrashed()
 *
 * @mixin \Eloquent
 */
class TargetStatistic extends BaseModelAbstract
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'result' => 'array',
        'value' => 'float',
    ];

    /**
     * The target model that this statistic belongs to
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The statistic that this belongs to
     */
    public function statistic(): BelongsTo
    {
        return $this->belongsTo(Statistic::class);
    }
}
