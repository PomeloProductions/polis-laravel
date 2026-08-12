<?php
declare(strict_types=1);

namespace App\Models\User;

use Polis\Models\User\ProfileImage as AtheniaProfileImage;

/**
 * Class ProfileImage
 *
 * @package App\Models\User
 * @property int $id
 * @property int|null $owner_id
 * @property string|null $name
 * @property string|null $caption
 * @property string $url
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $owner_type
 * @property string|null $source
 * @property string|null $alt
 * @property int $width
 * @property int $height
 * @property-read \App\Models\Organization\Organization|null $organization
 * @property-read ProfileImage|null $owner
 * @property-read \App\Models\User\User|null $user
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileImage onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereAlt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereCaption($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereHeight($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereName($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereOwnerId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereOwnerType($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereSource($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereUpdatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereUrl($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ProfileImage whereWidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileImage withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileImage withoutTrashed()
 * @mixin \Eloquent
 */
class ProfileImage extends AtheniaProfileImage
{
}