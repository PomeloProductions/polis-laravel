<?php

declare(strict_types=1);

namespace App\Models\User;

use Illuminate\Support\Carbon;
use Polis\Models\User\ExternalAccountConnection as AtheniaExternalAccountConnection;

/**
 * Class ExternalAccountConnection
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $owner_id
 * @property string|null $owner_type
 * @property string $provider
 * @property string|null $external_user_id
 * @property array|null $credentials
 * @property array|null $scopes
 * @property Carbon|null $token_expires_at
 * @property string $status
 * @property string|null $last_error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 *
 * @method static \Database\Factories\User\ExternalAccountConnectionFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAccountConnection onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ExternalAccountConnection setUseTableAlias(bool $useTableAlias)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAccountConnection withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAccountConnection withoutTrashed()
 *
 * @mixin \Eloquent
 */
class ExternalAccountConnection extends AtheniaExternalAccountConnection {}
