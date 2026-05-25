<?php

declare(strict_types=1);

namespace Polis\Tests\Traits;

use App\Models\Role;
use App\Models\User\User;

trait RolesTesting
{
    /**
     * @return User
     */
    protected function getUserOfRole($roleId)
    {
        /** @var User $user */
        $user = User::factory()->create();

        return $user->addRole($roleId);
    }

    /**
     * @return array
     */
    protected function rolesWithoutAdmins(array $withoutRoles = [])
    {
        $withoutRoles[] = Role::SUPER_ADMIN;

        return array_filter(Role::ROLES, function ($role) use ($withoutRoles) {
            return ! in_array($role, $withoutRoles);
        });
    }
}
