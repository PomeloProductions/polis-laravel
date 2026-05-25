<?php

declare(strict_types=1);

namespace Polis\Policies;

use App\Models\Asset;
use App\Models\User\User;
use Polis\Contracts\Models\IsAnEntityContract;

abstract class AssetPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $loggedInUser, IsAnEntityContract $entity)
    {
        return $entity->canUserManageEntity($loggedInUser);
    }

    /**
     * @return bool
     */
    public function create(User $loggedInUser, IsAnEntityContract $entity)
    {
        return $entity->canUserManageEntity($loggedInUser);
    }

    /**
     * @return bool
     */
    public function update(User $loggedInUser, IsAnEntityContract $entity, Asset $asset)
    {
        return $asset->owner_type == $entity->morphRelationName() && $asset->owner_id == $entity->id
            && $entity->canUserManageEntity($loggedInUser);
    }

    /**
     * @return bool
     */
    public function delete(User $loggedInUser, IsAnEntityContract $entity, Asset $asset)
    {
        return $asset->owner_type == $entity->morphRelationName() && $asset->owner_id == $entity->id
            && $entity->canUserManageEntity($loggedInUser);
    }
}
