<?php

declare(strict_types=1);

namespace Polis\Models\User;

use App\Models\Asset;
use App\Models\Organization\Organization;
use App\Models\User\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class ProfileImage
 *
 * @property int $id
 * @property int|null $owner_id
 * @property string|null $name
 * @property string|null $caption
 * @property string $url
 * @property Carbon|null $deleted_at
 * @property mixed|null $created_at
 * @property mixed|null $updated_at
 * @property string|null $owner_type
 * @property-read Organization|null $organization
 * @property-read Model|Eloquent $owner
 * @property-read User|null $user
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\User\ProfileImage newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\User\ProfileImage newQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\User\ProfileImage query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\ProfileImage whereCaption($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\ProfileImage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\ProfileImage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\ProfileImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\ProfileImage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\ProfileImage whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\ProfileImage whereOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\ProfileImage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\ProfileImage whereUrl($value)
 *
 * @mixin Eloquent
 *
 * @property string|null $source
 * @property string|null $alt
 * @property int $width
 * @property int $height
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage joinRelations($relations, $leftJoin = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileImage onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereAlt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereHeight($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereSource($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereWidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileImage withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileImage withoutTrashed()
 *
 * @mixin Eloquent
 */
class ProfileImage extends Asset
{
    public function organization(): HasOne
    {
        return $this->hasOne(Organization::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
