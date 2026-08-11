<?php
declare(strict_types=1);

namespace App\Models\Vote;

use Polis\Models\Vote\BallotItemOption as AtheniaBallotItemOption;

/**
 * Class BallotItemOption
 *
 * @package App\Models\Vote
 * @property int $id
 * @property int $ballot_item_id
 * @property int $vote_count
 * @property int $subject_id
 * @property string $subject_type
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Polis\Models\Vote\BallotItem $ballotItem
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $subject
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Polis\Models\Vote\Vote> $votes
 * @property-read int|null $votes_count
 * @method static \Database\Factories\Vote\BallotItemOptionFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallotItemOption onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption whereBallotItemId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption whereSubjectId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption whereSubjectType($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption whereUpdatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|BallotItemOption whereVoteCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallotItemOption withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallotItemOption withoutTrashed()
 * @mixin \Eloquent
 */
class BallotItemOption extends AtheniaBallotItemOption
{
}