<?php

declare(strict_types=1);

namespace Polis\Policies\Subscription;

use App\Models\Role;
use App\Models\Subscription\Subscription;
use App\Models\User\User;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Policies\BasePolicyAbstract;

abstract class SubscriptionPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $loggedInUser, IsAnEntityContract $entity)
    {
        return $entity->canUserManageEntity($loggedInUser, Role::ADMINISTRATOR);
    }

    /**
     * @return bool
     */
    public function create(User $loggedInUser, IsAnEntityContract $entity)
    {
        return $entity->canUserManageEntity($loggedInUser, Role::ADMINISTRATOR);
    }

    /**
     * @return bool
     */
    public function update(User $loggedInUser, IsAnEntityContract $entity, Subscription $subscription)
    {
        return $entity->canUserManageEntity($loggedInUser, Role::ADMINISTRATOR)
            && $subscription->subscriber_type == $entity->morphRelationName()
            && $subscription->subscriber_id == $entity->id;
    }
}
