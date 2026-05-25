<?php

declare(strict_types=1);

namespace Polis\Policies\Organization;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

abstract class OrganizationManagerPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $user, Organization $organization)
    {
        return $user->canManageOrganization($organization, Role::MANAGER);
    }

    /**
     * @return bool
     */
    public function create(User $user, Organization $organization)
    {
        return $user->canManageOrganization($organization, Role::ADMINISTRATOR);
    }

    /**
     * @return bool
     */
    public function update(User $user, Organization $organization, OrganizationManager $organizationManager)
    {
        return $organization->id === $organizationManager->organization_id &&
            $user->canManageOrganization($organization, Role::ADMINISTRATOR);
    }

    /**
     * @return bool
     */
    public function delete(User $user, Organization $organization, OrganizationManager $organizationManager)
    {
        return $organization->id === $organizationManager->organization_id &&
            $user->canManageOrganization($organization, Role::ADMINISTRATOR);
    }
}
