<?php

declare(strict_types=1);

namespace Polis\Models\Statistic;

use App\Models\Statistic\StatisticFilter;
use App\Models\Statistic\TargetStatistic;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;

/**
 * Class Statistic
 *
 * @property int $id
 * @property string $type
 * @property int $total
 * @property Carbon|null $deleted_at
 * @property \datetime|null $created_at
 * @property \datetime|null $updated_at
 * @property string|null $name
 * @property bool $public
 * @property int|null $owner_id
 * @property string|null $owner_type
 * @property-read Model|Eloquent|null $owner
 * @property-read Collection|StatisticFilter[] $statisticFilters
 * @property-read int|null $statistic_filters_count
 * @property-read Collection|TargetStatistic[] $targetStatistics
 * @property-read int|null $target_statistics_count
 * @property string $model
 * @property string $relation
 * @property-read Collection<int, StatisticFilter> $filters
 * @property-read int|null $filters_count
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statistic onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic whereModel($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic whereName($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic whereOwnerId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic whereOwnerType($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic wherePublic($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic whereRelation($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Statistic whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statistic withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statistic withoutTrashed()
 *
 * @mixin Eloquent
 */
class Statistic extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasValidationRules;

    /**
     * The filters that we use to determine what to count
     */
    public function filters(): HasMany
    {
        return $this->hasMany(StatisticFilter::class);
    }

    /**
     * Alias for backward compatibility
     */
    public function statisticFilters(): HasMany
    {
        return $this->filters();
    }

    /**
     * All instances of the target statistics in the system
     */
    public function targetStatistics(): HasMany
    {
        return $this->hasMany(TargetStatistic::class);
    }

    /**
     * The entity (User / Organization / …) that owns this statistic.
     *
     * A NULL owner means a GLOBAL / public statistic (the pre-owner
     * behaviour); a resolved owner means a per-user (or, later, per-org)
     * statistic. The `owner_type` column stores the morph alias returned by
     * the entity's morphRelationName() (e.g. 'user', 'organization').
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to statistics owned by the given entity.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOwnedBy(Builder $query, IsAnEntityContract $owner): Builder
    {
        return $query
            ->where('owner_type', $owner->morphRelationName())
            ->where('owner_id', $owner->getKey());
    }

    /**
     * Scope to the global (NULL-owner) statistics PLUS those owned by the
     * given entity — the common "show shared + mine" query. When no owner is
     * supplied this is just the global statistics.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeGlobalOrOwnedBy(Builder $query, ?IsAnEntityContract $owner = null): Builder
    {
        return $query->where(function (Builder $inner) use ($owner): void {
            $inner->whereNull('owner_type')->whereNull('owner_id');

            if ($owner !== null) {
                $inner->orWhere(function (Builder $owned) use ($owner): void {
                    $owned
                        ->where('owner_type', $owner->morphRelationName())
                        ->where('owner_id', $owner->getKey());
                });
            }
        });
    }

    /**
     * Scope to the global (NULL-owner) statistics only — the shared,
     * public definitions.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('owner_type')->whereNull('owner_id');
    }

    /**
     * {@inheritDoc}
     */
    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'name' => [
                    'string',
                ],
                'model' => [
                    'string',
                ],
                'relation' => [
                    'string',
                ],
                'public' => [
                    'boolean',
                ],
                'owner_type' => [
                    'nullable',
                    'string',
                ],
                'owner_id' => [
                    'nullable',
                    'integer',
                ],
                'statistic_filters' => [
                    'array',
                ],
                'statistic_filters.*' => [
                    'array',
                ],
                'statistic_filters.*.field' => [
                    'required',
                    'string',
                ],
                'statistic_filters.*.operator' => [
                    'required',
                    'string',
                ],
                'statistic_filters.*.value' => [
                    'nullable',
                    'string',
                ],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'name',
                    'model',
                    'relation',
                ],
            ],
            static::VALIDATION_RULES_UPDATE => [
                static::VALIDATION_PREPEND_NOT_PRESENT => [
                    'model',
                    'relation',
                ],
            ],
        ];
    }
}
