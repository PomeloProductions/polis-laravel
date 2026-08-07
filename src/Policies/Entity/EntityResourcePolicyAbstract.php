<?php

declare(strict_types=1);

namespace Polis\Policies\Entity;

use App\Models\Role;
use App\Models\User\User;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Policies\BaseBelongsToOrganizationPolicyAbstract;
use Polis\Policies\BasePolicyAbstract;
use Polis\Policies\Collection\CollectionPolicyAbstract;
use Polis\Policies\Payment\PaymentPolicyAbstract;

/**
 * Class EntityResourcePolicyAbstract
 *
 * A reusable authorization base for resources owned by an
 * {@see IsAnEntityContract} entity (a User or an Organization) — the polymorphic
 * counterpart to {@see BaseBelongsToOrganizationPolicyAbstract},
 * which only understands Organizations.
 *
 * Where the org policy hard-codes `$user->canManageOrganization(...)`, this base
 * delegates to `$entity->canUserManageEntity(...)`, so the SAME policy governs a
 * User-owned resource and an Organization-owned resource (and any future entity
 * type). This generalizes the pattern already used by
 * {@see CollectionPolicyAbstract} and
 * {@see PaymentPolicyAbstract}.
 *
 * The `create` gate receives the entity as a route-bound parameter. The
 * `view` / `update` / `delete` gates receive the model itself and read the
 * owning entity off its polymorphic `owner` relation, asserting management of
 * THAT entity — so cross-entity access is denied without the caller having to
 * re-pass the entity.
 *
 * Super-admins pass every gate via {@see BasePolicyAbstract::before()}.
 */
abstract class EntityResourcePolicyAbstract extends BasePolicyAbstract
{
    /**
     * Whether management actions (create/update/delete) require the caller to be
     * an ADMINISTRATOR of the entity rather than merely a MANAGER. Subclasses may
     * override.
     */
    protected bool $requiresAdminForManagement = false;

    /**
     * The role required to view a non-public resource. Defaults to MANAGER.
     */
    protected function viewRole(): int
    {
        return Role::MANAGER;
    }

    /**
     * The role required to manage (create/update/delete) a resource.
     */
    protected function managementRole(): int
    {
        return $this->requiresAdminForManagement ? Role::ADMINISTRATOR : Role::MANAGER;
    }

    /**
     * Reads the owning entity off a model's polymorphic `owner` relation.
     */
    protected function resolveOwner(object $model): ?IsAnEntityContract
    {
        /** @var IsAnEntityContract|null $owner */
        $owner = $model->owner ?? null;

        return $owner;
    }

    /**
     * Anyone authenticated may attempt to list; the controller/request scopes
     * the result set to the entity in the route. Subclasses may tighten this.
     *
     * @return bool
     */
    public function all(User $user)
    {
        return true;
    }

    /**
     * A caller may create a resource for an entity they manage.
     *
     * @return bool
     */
    public function create(User $user, IsAnEntityContract $entity)
    {
        return $entity->canUserManageEntity($user, $this->managementRole());
    }

    /**
     * A caller may view a resource if it is public or they manage its owner.
     *
     * @return bool
     */
    public function view(User $user, object $model)
    {
        $entity = $this->resolveOwner($model);

        if (($model->is_public ?? false)) {
            return true;
        }

        return $entity !== null && $entity->canUserManageEntity($user, $this->viewRole());
    }

    /**
     * A caller may update a resource if they manage its owning entity.
     *
     * @return bool
     */
    public function update(User $user, object $model)
    {
        $entity = $this->resolveOwner($model);

        return $entity !== null && $entity->canUserManageEntity($user, $this->managementRole());
    }

    /**
     * A caller may delete a resource if they manage its owning entity.
     *
     * @return bool
     */
    public function delete(User $user, object $model)
    {
        $entity = $this->resolveOwner($model);

        return $entity !== null && $entity->canUserManageEntity($user, $this->managementRole());
    }
}
