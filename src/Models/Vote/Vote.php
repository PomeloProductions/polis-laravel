<?php

declare(strict_types=1);

namespace Polis\Models\Vote;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Polis\Events\Vote\VoteCreatedEvent;
use Polis\Models\BaseModelAbstract;

/**
 * Class Vote
 *
 * @property int $id
 * @property int $ballot_item_option_id
 * @property int $ballot_completion_id
 * @property int $result
 * @property Carbon|null $deleted_at
 * @property mixed|null $created_at
 * @property mixed|null $updated_at
 * @property-read \App\Models\Vote\BallotCompletion $ballotCompletion
 * @property-read \App\Models\Vote\BallotItemOption $ballotItemOption
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Vote\Vote newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Vote\Vote newQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Vote\Vote query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Vote whereBallotCompletionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Vote whereBallotItemOptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Vote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Vote whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Vote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Vote whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Vote whereUpdatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote joinRelations($relations, $leftJoin = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Vote whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Vote extends BaseModelAbstract
{
    /**
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => VoteCreatedEvent::class,
    ];

    /**
     * The ballot completion that this is part of
     */
    public function ballotCompletion(): BelongsTo
    {
        return $this->belongsTo(BallotCompletion::class);
    }

    /**
     * The subject that was voted for
     */
    public function ballotItemOption(): BelongsTo
    {
        return $this->belongsTo(BallotItemOption::class);
    }
}
