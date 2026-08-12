<?php
declare(strict_types=1);

namespace App\Models\User;

use Polis\Models\User\PasswordToken as AtheniaPasswordToken;

/**
 * Class PasswordToken
 *
 * @package App\Models\User
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User\User $user
 * @method static \Database\Factories\User\PasswordTokenFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordToken onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken whereToken($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken whereUpdatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|PasswordToken whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordToken withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordToken withoutTrashed()
 * @mixin \Eloquent
 */
class PasswordToken extends AtheniaPasswordToken
{
}