<?php
declare(strict_types=1);

namespace App\Models;

use Polis\Models\Asset as AtheniaAsset;

/**
 * Class Asset
 *
 * @package App\Models
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
 * @property-read Asset|null $owner
 * @method static \Database\Factories\AssetFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereAlt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereCaption($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereHeight($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereName($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereOwnerId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereOwnerType($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereSource($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereUpdatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereUrl($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Asset whereWidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset withoutTrashed()
 * @mixin \Eloquent
 */
class Asset extends AtheniaAsset
{
}