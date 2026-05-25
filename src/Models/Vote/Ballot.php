<?php

declare(strict_types=1);

namespace Polis\Models\Vote;

use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Polis\Models\BaseModelAbstract;

/**
 * Class Ballot
 *
 * @property int $id
 * @property string|null $name
 * @property string $type
 * @property Carbon|null $deleted_at
 * @property mixed|null $created_at
 * @property mixed|null $updated_at
 * @property-read Collection|\App\Models\Vote\BallotCompletion[] $ballotCompletions
 * @property-read int|null $ballot_completions_count
 * @property-read Collection|\App\Models\Vote\BallotItem[] $ballotItems
 * @property-read int|null $ballot_items_count
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Vote\Ballot newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Vote\Ballot newQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Vote\Ballot query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Ballot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Ballot whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Ballot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Ballot whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Ballot whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vote\Ballot whereUpdatedAt($value)
 *
 * @mixin Eloquent
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot joinRelations($relations, $leftJoin = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ballot onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Ballot whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ballot withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ballot withoutTrashed()
 *
 * @mixin Eloquent
 */
class Ballot extends BaseModelAbstract
{
    /**
     * This ballot type when there is a single subject and the user chooses yes or no
     */
    const TYPE_SINGLE_OPTION = 'single_option';

    /**
     * The ballot type for when there will be multiple options within each item
     */
    const TYPE_MULTIPLE_OPTIONS = 'multiple_options';

    /**
     * The ballot type for when we allow the user to rank their options
     */
    const TYPE_RANKED_CHOICE = 'ranked_choice';

    /**
     * All times someone has completed this ballot
     */
    public function ballotCompletions(): HasMany
    {
        return $this->hasMany(BallotCompletion::class);
    }

    /**
     * All subjects contained in this ballot
     */
    public function ballotItems(): HasMany
    {
        return $this->hasMany(BallotItem::class);
    }
}
