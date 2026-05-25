<?php

declare(strict_types=1);

namespace Polis\Models\Subscription;

use App\Models\Payment\LineItem;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentMethod;
use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Polis\Contracts\Models\HasPaymentsContract;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasPayments;
use Polis\Models\Traits\HasValidationRules;
use Polis\Validators\Subscription\MembershipPlanRateIsActiveValidator;
use Polis\Validators\Subscription\PaymentMethodIsOwnedByEntityValidator;

/**
 * Class Subscription
 *
 * @property int $id
 * @property int $membership_plan_rate_id
 * @property int $payment_method_id
 * @property int $subscriber_id
 * @property string $subscriber_type
 * @property mixed|null $last_renewed_at
 * @property mixed|null $subscribed_at
 * @property mixed|null $expires_at
 * @property mixed|null $canceled_at
 * @property bool $recurring
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $is_trial
 * @property-read null|string $formatted_cost
 * @property-read null|string $formatted_expires_at
 * @property-read Collection|LineItem[] $lineItems
 * @property-read int|null $line_items_count
 * @property-read MembershipPlanRate $membershipPlanRate
 * @property-read PaymentMethod $paymentMethod
 * @property-read Collection|Payment[] $payments
 * @property-read int|null $payments_count
 * @property-read Model|Eloquent $subscriber
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Subscription\Subscription newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Subscription\Subscription newQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Subscription\Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereCanceledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereIsTrial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereLastRenewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereMembershipPlanRateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription wherePaymentMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereRecurring($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereSubscribedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereSubscriberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereSubscriberType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\Subscription whereUpdatedAt($value)
 *
 * @mixin Eloquent
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription joinRelations($relations, $leftJoin = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Subscription whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription withoutTrashed()
 *
 * @mixin Eloquent
 */
class Subscription extends BaseModelAbstract implements HasPaymentsContract, HasValidationRulesContract
{
    use HasPayments, HasValidationRules;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_renewed_at' => 'datetime:c',
        'subscribed_at' => 'datetime:c',
        'expires_at' => 'datetime:c',
        'canceled_at' => 'datetime:c',
    ];

    /**
     * The membership plan rate this subscription is signed up for
     */
    public function membershipPlanRate(): BelongsTo
    {
        return $this->belongsTo(MembershipPlanRate::class);
    }

    /**
     * The payment method that is used to renew this subscription
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * The subscriber this subscription is for
     */
    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * {@inheritDoc}
     */
    public function morphRelationName(): string
    {
        return 'subscription';
    }

    /**
     * Determines whether or not this subscription is good for a lifetime
     */
    public function isLifetime(): bool
    {
        return $this->membershipPlanRate->membershipPlan->duration == MembershipPlan::DURATION_LIFETIME;
    }

    /**
     * Formats the expires at date string
     *
     * @return null|string
     */
    public function getFormattedExpiresAtAttribute()
    {
        return $this->expires_at ? $this->expires_at->format('F jS Y') : null;
    }

    /**
     * Formats the cost to be human readable
     *
     * @return null|string
     */
    public function getFormattedCostAttribute()
    {
        return $this->membershipPlanRate && $this->membershipPlanRate->cost ?
            number_format((float) $this->membershipPlanRate->cost, 2) : null;
    }

    /**
     * Build the model validation rules
     *
     * @param  array  $params
     */
    public function buildModelValidationRules(...$params): array
    {
        return [
            self::VALIDATION_RULES_BASE => [
                'cancel' => [
                    'boolean',
                ],
                'membership_plan_rate_id' => [
                    'integer',
                    Rule::exists('membership_plan_rates', 'id'),
                    MembershipPlanRateIsActiveValidator::KEY,
                ],
                'payment_method_id' => [
                    'integer',
                    Rule::exists('payment_methods', 'id'),
                    PaymentMethodIsOwnedByEntityValidator::KEY,
                ],
                'is_trial' => [
                    'boolean',
                ],
                'recurring' => [
                    'boolean',
                ],
            ],
            self::VALIDATION_RULES_CREATE => [
                self::VALIDATION_PREPEND_REQUIRED_UNLESS.'is_trial,true' => [
                    'payment_method_id',
                ],
                self::VALIDATION_PREPEND_REQUIRED => [
                    'membership_plan_rate_id',
                ],
                self::VALIDATION_PREPEND_NOT_PRESENT => [
                    'cancel',
                ],
            ],
            self::VALIDATION_RULES_UPDATE => [
                self::VALIDATION_PREPEND_NOT_PRESENT => [
                    'membership_plan_rate_id',
                    'is_trial',
                ],
            ],
        ];
    }
}
