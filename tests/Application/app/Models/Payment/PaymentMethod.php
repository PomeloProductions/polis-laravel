<?php
declare(strict_types=1);

namespace App\Models\Payment;

use Polis\Models\Payment\PaymentMethod as AtheniaPaymentMethod;

/**
 * Class PaymentMethod
 *
 * @package App\Models\Payment
 * @property int $id
 * @property int $owner_id
 * @property string $owner_type
 * @property string|null $payment_method_key
 * @property string $payment_method_type
 * @property string|null $identifier
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $default
 * @property string|null $brand
 * @property string|null $exp_month
 * @property string|null $exp_year
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @method static \Database\Factories\Payment\PaymentMethodFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereBrand($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereDefault($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereExpMonth($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereExpYear($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereIdentifier($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereOwnerId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereOwnerType($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod wherePaymentMethodKey($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod wherePaymentMethodType($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PaymentMethod whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentMethod withoutTrashed()
 * @mixin \Eloquent
 */
class PaymentMethod extends AtheniaPaymentMethod
{
}