<?php

declare(strict_types=1);

namespace Polis\Policies\Messaging;

use App\Models\Organization\Organization;
use App\Models\Role;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

/**
 * Class EmailTemplatePolicyAbstract
 *
 * Gates the admin endpoints that list + edit org-scoped email templates.
 *
 * Authorization model:
 *   - list/view: any user that can manage the organization at MANAGER level
 *     (admins of the org will satisfy MANAGER too).
 *   - update/delete: requires ADMINISTRATOR — editing copy that goes out
 *     to every member of the org is a meaningful action.
 *
 * `Polis\Policies\BasePolicyAbstract::before` already grants SUPER_ADMIN
 * a global override, so an app-level super admin can manage any org's
 * templates (this is what powers the "global default" edits).
 */
abstract class EmailTemplatePolicyAbstract extends BasePolicyAbstract
{
    /**
     * Org managers (or higher) may list templates for an organization.
     */
    public function all(User $user, Organization $organization): bool
    {
        return $user->canManageOrganization($organization, Role::MANAGER);
    }

    /**
     * Org managers (or higher) may view a single template.
     */
    public function view(User $user, Organization $organization): bool
    {
        return $user->canManageOrganization($organization, Role::MANAGER);
    }

    /**
     * Org administrators may edit templates.
     */
    public function update(User $user, Organization $organization): bool
    {
        return $user->canManageOrganization($organization, Role::ADMINISTRATOR);
    }

    /**
     * Org administrators may revert a template to default.
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $user->canManageOrganization($organization, Role::ADMINISTRATOR);
    }
}
