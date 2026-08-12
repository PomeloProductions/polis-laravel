<?php
declare(strict_types=1);

namespace App\Models\Statistic;

use Polis\Models\Statistic\StatisticFilter as AtheniaStatisticFilter;

/**
 * Class StatisticFilter
 *
 * @package App\Models\Statistic
 * @property int $id
 * @property int $statistic_id
 * @property string $field
 * @property string $operator
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Statistic\Statistic $statistic
 * @method static \Database\Factories\Statistic\StatisticFilterFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatisticFilter onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter whereField($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter whereOperator($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter whereStatisticId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter whereUpdatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|StatisticFilter whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatisticFilter withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatisticFilter withoutTrashed()
 * @mixin \Eloquent
 */
class StatisticFilter extends AtheniaStatisticFilter
{
}