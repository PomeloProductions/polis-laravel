<?php

declare(strict_types=1);

namespace Polis\Policies;

use App\Models\Role;
use App\Models\User\User;
use Polis\Contracts\Policies\BasePolicyContract;

/**
 * Class BasePolicyAbstract
 */
abstract class BasePolicyAbstract implements BasePolicyContract
{
    /**
     * No one in this app should be able to see everything
     *
     * @return null|bool
     */
    public function before(User $user)
    {
        return $user->hasRole([Role::SUPER_ADMIN]) ?: null;
    }
}
