<?php

declare(strict_types=1);

namespace Polis\Policies\Collection;

use App\Models\Collection\Collection;
use App\Models\Role;
use App\Models\User\User;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Policies\BasePolicyAbstract;

abstract class CollectionPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $user)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function create(User $user, IsAnEntityContract $entity)
    {
        return $entity->canUserManageEntity($user, Role::MANAGER);
    }

    /**
     * @return bool
     */
    public function view(User $user, Collection $collection)
    {
        /** @var IsAnEntityContract $entity */
        $entity = $collection->owner;

        return $collection->is_public || $entity->canUserManageEntity($user, Role::MANAGER);
    }

    /**
     * @return bool
     */
    public function update(User $user, Collection $collection)
    {
        /** @var IsAnEntityContract $entity */
        $entity = $collection->owner;

        return $entity->canUserManageEntity($user, Role::MANAGER);
    }

    /**
     * @return bool
     */
    public function delete(User $user, Collection $collection)
    {
        /** @var IsAnEntityContract $entity */
        $entity = $collection->owner;

        return $entity->canUserManageEntity($user, Role::MANAGER);
    }
}
