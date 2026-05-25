<?php

declare(strict_types=1);

namespace Polis\Models\Organization;

use App\Models\Organization\Organization;
use App\Models\Role;
use App\Models\User\User;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Polis\Contracts\Models\BelongsToOrganizationContract;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\BelongsToOrganization;
use Polis\Models\Traits\HasValidationRules;

/**
 * Class OrganizationManager
 *
 * @property int $id
 * @property int $user_id
 * @property int $organization_id
 * @property int $role_id
 * @property Carbon|null $deleted_at
 * @property mixed|null $created_at
 * @property mixed|null $updated_at
 * @property-read Organization $organization
 * @property-read Role $role
 * @property-read User $user
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Organization\OrganizationManager newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Organization\OrganizationManager newQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Organization\OrganizationManager query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization\OrganizationManager whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization\OrganizationManager whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization\OrganizationManager whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization\OrganizationManager whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization\OrganizationManager whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization\OrganizationManager whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization\OrganizationManager whereUserId($value)
 *
 * @mixin Eloquent
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager joinRelations($relations, $leftJoin = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationManager onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|OrganizationManager whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationManager withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationManager withoutTrashed()
 *
 * @mixin Eloquent
 */
class OrganizationManager extends BaseModelAbstract implements BelongsToOrganizationContract, HasValidationRulesContract
{
    use BelongsToOrganization, HasValidationRules;

    /**
     * The related organization
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * The related user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Build the model validation rules
     *
     * @param  array  $params
     */
    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'role_id' => [
                    'required',
                    'integer',
                    Rule::in(Role::ENTITY_ROLES),
                ],
                'email' => [
                    'string',
                    'email',
                ],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'email',
                ],
            ],
            static::VALIDATION_RULES_UPDATE => [
                static::VALIDATION_PREPEND_NOT_PRESENT => [
                    'email',
                ],
            ],
        ];
    }
}
