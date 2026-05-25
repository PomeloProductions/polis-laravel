<?php

declare(strict_types=1);

namespace Polis\Policies\Organization;

use App\Models\Organization\Organization;
use App\Models\Role;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

abstract class OrganizationPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $user)
    {
        return false;
    }

    /**
     * @return bool
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function view(User $user, Organization $organization)
    {
        return $user->canManageOrganization($organization, Role::MANAGER);
    }

    /**
     * @return bool
     */
    public function update(User $user, Organization $organization)
    {
        return $user->canManageOrganization($organization, Role::MANAGER);
    }

    /**
     * @return bool
     */
    public function delete(User $user, Organization $organization)
    {
        return $user->canManageOrganization($organization, Role::ADMINISTRATOR);
    }
}
