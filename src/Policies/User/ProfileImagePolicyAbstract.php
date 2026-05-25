<?php

declare(strict_types=1);

namespace Polis\Policies\User;

use App\Models\User\User;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Policies\BasePolicyAbstract;

abstract class ProfileImagePolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function create(User $loggedInUser, IsAnEntityContract $entity)
    {
        return $entity->canUserManageEntity($loggedInUser);
    }
}
