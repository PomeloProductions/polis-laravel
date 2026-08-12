<?php
declare(strict_types=1);

namespace App\Models\Subscription;

use Polis\Models\Subscription\MembershipPlanRate as AtheniaMembershipPlanRate;

/**
 * Class MembershipPlanRate
 *
 * @package App\Models\Subscription
 * @property int $id
 * @property int $membership_plan_id
 * @property float $cost
 * @property int $active
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Subscription\MembershipPlan $membershipPlan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @method static \Database\Factories\Subscription\MembershipPlanRateFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipPlanRate onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereActive($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereCost($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereMembershipPlanId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipPlanRate withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipPlanRate withoutTrashed()
 * @mixin \Eloquent
 */
class MembershipPlanRate extends AtheniaMembershipPlanRate
{
}