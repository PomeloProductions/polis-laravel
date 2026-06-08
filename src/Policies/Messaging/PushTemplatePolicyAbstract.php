<?php

declare(strict_types=1);

namespace Polis\Policies\Messaging;

use App\Models\Organization\Organization;
use App\Models\Role;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

/**
 * Class PushTemplatePolicyAbstract
 *
 * Gates the admin endpoints that list + edit org-scoped push templates.
 * Mirrors EmailTemplatePolicyAbstract one-to-one. See its class docblock
 * for the authorization model.
 */
abstract class PushTemplatePolicyAbstract extends BasePolicyAbstract
{
    public function all(User $user, Organization $organization): bool
    {
        return $user->canManageOrganization($organization, Role::MANAGER);
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->canManageOrganization($organization, Role::MANAGER);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->canManageOrganization($organization, Role::ADMINISTRATOR);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->canManageOrganization($organization, Role::ADMINISTRATOR);
    }
}
