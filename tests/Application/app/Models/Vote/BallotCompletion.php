<?php
declare(strict_types=1);

namespace App\Models\Vote;

use Polis\Models\Vote\BallotCompletion as AtheniaBallotCompletion;

/**
 * Class BallotCompletion
 *
 * @package App\Models\Vote
 * @property int $id
 * @property int $ballot_id
 * @property int $user_id
 * @property string|null $completed_at
 * @property string|null $response
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Polis\Models\Vote\Ballot $ballot
 * @property-read \App\Models\User\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Polis\Models\Vote\Vote> $votes
 * @property-read int|null $votes_count
 * @method static \Database\Factories\Vote\BallotCompletionFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallotCompletion onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion whereBallotId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion whereCompletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion whereResponse($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion whereUpdatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotCompletion whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallotCompletion withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallotCompletion withoutTrashed()
 * @mixin \Eloquent
 */
class BallotCompletion extends AtheniaBallotCompletion
{
}