<?php

declare(strict_types=1);

namespace Polis\Policies;

use App\Models\Organization\Organization;
use App\Models\Role;
use App\Models\User\User;
use Polis\Contracts\Models\BelongsToOrganizationContract;

/**
 * Class BaseBelongsToOrganizationPolicyAbstract
 */
abstract class BaseBelongsToOrganizationPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @var bool Whether or not this policy requires that the logged in user is an admin for modifying data
     */
    protected bool $requiresAdminForManagement = false;

    /**
     * Logged in users can index devices within an organization they belong to
     *
     * @return bool
     */
    public function all(User $user, Organization $organization)
    {
        return $user->canManageOrganization($organization);
    }

    /**
     * Logged in users that can manage the passed in organization can create devices for that organization
     *
     * @return bool
     */
    public function create(User $user, Organization $organization)
    {
        $role = $this->requiresAdminForManagement ? Role::ADMINISTRATOR : Role::MANAGER;

        return $user->canManageOrganization($organization, $role);
    }

    /**
     * Logged in users can view devices within an organization they belong to
     *
     * @return bool
     */
    public function view(User $user, Organization $organization, BelongsToOrganizationContract $model)
    {
        return $model->organization_id == $organization->id && $user->canManageOrganization($organization);
    }

    /**
     * Logged in users can view devices within an organization they belong to
     *
     * @return bool
     */
    public function update(User $user, Organization $organization, BelongsToOrganizationContract $model)
    {
        $role = $this->requiresAdminForManagement ? Role::ADMINISTRATOR : Role::MANAGER;

        return $model->organization_id == $organization->id && $user->canManageOrganization($organization, $role);
    }

    /**
     * Logged in users can view devices within an organization they belong to
     *
     * @return bool
     */
    public function delete(User $user, Organization $organization, BelongsToOrganizationContract $model)
    {
        $role = $this->requiresAdminForManagement ? Role::ADMINISTRATOR : Role::MANAGER;

        return $model->organization_id == $organization->id && $user->canManageOrganization($organization, $role);
    }
}
