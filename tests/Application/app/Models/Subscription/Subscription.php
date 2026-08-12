<?php
declare(strict_types=1);

namespace App\Models\Subscription;

use Polis\Models\Subscription\Subscription as AtheniaSubscription;

/**
 * Class Subscription
 *
 * @package App\Models\Subscription
 * @property int $id
 * @property int $membership_plan_rate_id
 * @property int $payment_method_id
 * @property int $subscriber_id
 * @property string $subscriber_type
 * @property \Illuminate\Support\Carbon|null $last_renewed_at
 * @property \Illuminate\Support\Carbon|null $subscribed_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $canceled_at
 * @property int $recurring
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $is_trial
 * @property-read null|string $formatted_cost
 * @property-read null|string $formatted_expires_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment\LineItem> $lineItems
 * @property-read int|null $line_items_count
 * @property-read \App\Models\Subscription\MembershipPlanRate $membershipPlanRate
 * @property-read \App\Models\Payment\PaymentMethod $paymentMethod
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $subscriber
 * @method static \Database\Factories\Subscription\SubscriptionFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereCanceledAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereExpiresAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereIsTrial($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereLastRenewedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereMembershipPlanRateId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription wherePaymentMethodId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereRecurring($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereSubscribedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereSubscriberId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereSubscriberType($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription withoutTrashed()
 * @mixin \Eloquent
 */
class Subscription extends AtheniaSubscription
{
}