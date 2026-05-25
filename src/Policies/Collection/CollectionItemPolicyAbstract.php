<?php

declare(strict_types=1);

namespace Polis\Policies\Collection;

use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use App\Models\Role;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

abstract class CollectionItemPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $user, Collection $collection)
    {
        return $collection->is_public || $collection->owner->canUserManageEntity($user, Role::MANAGER);
    }

    /**
     * @return bool
     */
    public function create(User $user, Collection $collection)
    {
        return $collection->owner->canUserManageEntity($user, Role::MANAGER);
    }

    /**
     * @return bool
     */
    public function view(User $user, Collection $collection, CollectionItem $collectionItem)
    {
        return $collection->id == $collectionItem->collection_id &&
            ($collection->is_public || $collection->owner->canUserManageEntity($user, Role::MANAGER));
    }

    /**
     * @return bool
     */
    public function update(User $user, Collection $collection, CollectionItem $collectionItem)
    {
        return $collection->id == $collectionItem->collection_id &&
            $collection->owner->canUserManageEntity($user, Role::MANAGER);
    }

    /**
     * @return bool
     */
    public function delete(User $user, Collection $collection, CollectionItem $collectionItem)
    {
        return $collection->id == $collectionItem->collection_id &&
            $collection->owner->canUserManageEntity($user, Role::MANAGER);
    }
}
