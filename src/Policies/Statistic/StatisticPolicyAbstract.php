<?php

declare(strict_types=1);

namespace Polis\Policies\Statistic;

use App\Models\Role;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

abstract class StatisticPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $loggedInUser)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function view(User $loggedInUser)
    {
        return $loggedInUser->hasRole([
            Role::CONTENT_EDITOR,
            Role::SUPPORT_STAFF,
        ]);
    }

    /**
     * @return bool
     */
    public function create(User $loggedInUser)
    {
        return $loggedInUser->hasRole([
            Role::CONTENT_EDITOR,
        ]);
    }

    /**
     * @return bool
     */
    public function update(User $loggedInUser)
    {
        return $loggedInUser->hasRole([
            Role::CONTENT_EDITOR,
        ]);
    }

    /**
     * @return bool
     */
    public function delete(User $loggedInUser)
    {
        return $loggedInUser->hasRole([
            Role::CONTENT_EDITOR,
        ]);
    }
}
