<?php

declare(strict_types=1);

namespace Polis\Models\Messaging;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Polis\Contracts\Models\Messaging\CanReceivePushNotificationContract;
use Polis\Models\BaseModelAbstract;

/**
 * Class PushNotifications
 *
 * @property int $id
 * @property int $user_id
 * @property string $push_notification_key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read CanReceivePushNotificationContract $owner
 *
 * @mixin Eloquent
 *
 * @property int $owner_id
 * @property string $owner_type
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PushNotificationKey onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey whereOwnerId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey whereOwnerType($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey wherePushNotificationKey($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PushNotificationKey whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PushNotificationKey withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PushNotificationKey withoutTrashed()
 *
 * @mixin \Eloquent
 */
class PushNotificationKey extends BaseModelAbstract
{
    /**
     * @var string Table override due to laravel bug
     */
    protected $table = 'push_notification_keys';

    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
    }
}
