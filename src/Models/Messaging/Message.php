<?php

declare(strict_types=1);

namespace Polis\Models\Messaging;

use App\Models\Messaging\Thread;
use App\Models\User\User;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Polis\Contracts\Models\HasPolicyContract;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Events\Messaging\MessageCreatedEvent;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;

/**
 * Class Message
 *
 * @property int $id
 * @property string|null $email
 * @property string|null $subject
 * @property string|null $template
 * @property array $data
 * @property int|null $to_id
 * @property int|null $from_id
 * @property int|null $thread_id
 * @property array|null $via
 * @property string|null $action
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $seen_at
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $reply_to_email
 * @property string|null $reply_to_name
 * @property-read User|null $from
 * @property-read Thread|null $thread
 * @property-read User|null $to
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\User\Message newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\User\Message newQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\User\Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereFromId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereReplyToEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereReplyToName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereScheduledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereThreadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\Message whereVia($value)
 *
 * @mixin Eloquent
 *
 * @property string|null $to_type
 * @property string|null $from_type
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message joinRelations($relations, $leftJoin = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message whereFromType($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Message whereToType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message withoutTrashed()
 *
 * @mixin Eloquent
 */
class Message extends BaseModelAbstract implements HasPolicyContract, HasValidationRulesContract
{
    use HasValidationRules;

    const VIA_EMAIL = 'email';

    const VIA_SLACK = 'slack';

    const VIA_SMS = 'sms';

    const VIA_PUSH_NOTIFICATION = 'push';

    /**
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'via' => 'array',
        'seen_at' => 'datetime',
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    /**
     * Array of events that need to be dispatched
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => MessageCreatedEvent::class,
    ];

    /**
     * Each message belongs to a user
     */
    public function from(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The thread that this message is in
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Each message belongs to a user
     */
    public function to(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Build the model validation rules
     *
     * @param  array  $params
     */
    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'message' => [
                    'string',
                ],
                'seen' => [
                    'boolean',
                ],
                'template' => [
                    Rule::in([
                        'contact',
                    ]),
                ],
                'data' => [
                    'array',
                ],
            ],
            static::VALIDATION_RULES_UPDATE => [
                static::VALIDATION_PREPEND_NOT_PRESENT => [
                    'message',
                ],
            ],
        ];
    }
}
