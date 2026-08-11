<?php
declare(strict_types=1);

namespace App\Models\Subscription;

use Polis\Models\Subscription\MembershipPlan as AtheniaMembershipPlan;

/**
 * Class MembershipPlan
 *
 * @package App\Models\Subscription
 * @property int $id
 * @property string $name
 * @property string $duration
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $description
 * @property string $entity_type
 * @property int $default
 * @property int|null $trial_period
 * @property-read \App\Models\Subscription\MembershipPlanRate|null $currentRate
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Feature> $features
 * @property-read int|null $features_count
 * @property-read null|float $current_cost
 * @property-read null|float $current_rate_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription\MembershipPlanRate> $membershipPlanRates
 * @property-read int|null $membership_plan_rates_count
 * @method static \Database\Factories\Subscription\MembershipPlanFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipPlan onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereDefault($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereDescription($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereDuration($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereEntityType($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereName($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereTrialPeriod($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipPlan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipPlan withoutTrashed()
 * @mixin \Eloquent
 */
class MembershipPlan extends AtheniaMembershipPlan
{
}