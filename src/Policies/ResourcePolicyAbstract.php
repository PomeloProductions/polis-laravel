<?php

declare(strict_types=1);

namespace Polis\Policies;

use App\Models\User\User;

abstract class ResourcePolicyAbstract extends BasePolicyAbstract
{
    /**
     * Every logged in user can index resources
     *
     * @return bool
     */
    public function all(User $user)
    {
        return true;
    }
}
