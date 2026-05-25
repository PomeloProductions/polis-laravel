<?php

declare(strict_types=1);

namespace Polis\Models\Subscription;

use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\Subscription;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Polis\Models\BaseModelAbstract;

/**
 * Class MembershipPlanRate
 *
 * @property int $id
 * @property int $membership_plan_id
 * @property float $cost
 * @property bool $active
 * @property Carbon|null $deleted_at
 * @property mixed|null $created_at
 * @property mixed|null $updated_at
 * @property-read MembershipPlan $membershipPlan
 * @property-read Collection|Subscription[] $subscriptions
 * @property-read int|null $subscriptions_count
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Subscription\MembershipPlanRate newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Subscription\MembershipPlanRate newQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Subscription\MembershipPlanRate query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlanRate whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlanRate whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlanRate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlanRate whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlanRate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlanRate whereMembershipPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlanRate whereUpdatedAt($value)
 *
 * @mixin Eloquent
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate joinRelations($relations, $leftJoin = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipPlanRate onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlanRate whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipPlanRate withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipPlanRate withoutTrashed()
 *
 * @mixin Eloquent
 */
class MembershipPlanRate extends BaseModelAbstract
{
    /**
     * The membership plan this is related to
     */
    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    /**
     * All subscriptions that have been signed up for this membership plan rate
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
