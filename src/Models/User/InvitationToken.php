<?php

declare(strict_types=1);

namespace Polis\Models\User;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Role;

/**
 * Class InvitationToken
 *
 * @property int $id
 * @property string $token
 * @property int|null $role_id
 * @property Carbon|null $used_at
 * @property Carbon|null $deleted_at
 * @property mixed|null $created_at
 * @property mixed|null $updated_at
 * @property-read Role|null $role
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\User\InvitationToken newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\User\InvitationToken newQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\User\InvitationToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\InvitationToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\InvitationToken whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\InvitationToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\InvitationToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\InvitationToken whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\InvitationToken whereUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\InvitationToken whereUpdatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken joinRelations($relations, $leftJoin = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationToken onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|InvitationToken whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationToken withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationToken withoutTrashed()
 *
 * @mixin \Eloquent
 */
class InvitationToken extends BaseModelAbstract
{
    /**
     * The role relation for this invitation token
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if this invitation token has been used
     */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Swagger definition below for an invitation token...
     *
     * @SWG\Definition(
     *     type="object",
     *     definition="InvitationToken",
     *
     *     @SWG\Property(
     *         property="token",
     *         type="string",
     *         maxLength=40,
     *         description="The invitation token that was generated."
     *     ),
     *     @SWG\Property(
     *         property="role_id",
     *         type="integer",
     *         format="int32",
     *         description="The role ID that will be assigned when this invitation is accepted."
     *     ),
     *     @SWG\Property(
     *         property="used_at",
     *         type="string",
     *         format="date-time",
     *         description="UTC date of when this invitation was used."
     *     ),
     * )
     */
}
