<?php

declare(strict_types=1);

namespace Polis\Testing;

use App\Models\Role;
use App\Models\User\User;

/**
 * Shipped role-assertion helpers for authorization Feature tests. Promoted from
 * the package's internal `Polis\Tests\Traits\RolesTesting` so consuming
 * applications can iterate "every non-admin role is forbidden" without
 * re-declaring the helper in every app.
 */
trait RolesTesting
{
    /**
     * Create a user carrying the given role.
     *
     * @param  int  $roleId
     * @return User
     */
    protected function getUserOfRole($roleId)
    {
        /** @var User $user */
        $user = User::factory()->create();

        return $user->addRole($roleId);
    }

    /**
     * Every role except the super admin (and any additionally excluded roles).
     * Useful for asserting that non-privileged roles receive a 403.
     *
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
