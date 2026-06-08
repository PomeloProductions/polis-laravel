<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Policies;

use Polis\Policies\BaseBelongsToOrganizationPolicyAbstract;

/**
 * Concrete subclass of BaseBelongsToOrganizationPolicyAbstract that flips
 * the $requiresAdminForManagement flag to exercise the ADMINISTRATOR-role
 * branch of create / update / delete.
 */
class BaseBelongsToOrganizationAdminPolicy extends BaseBelongsToOrganizationPolicyAbstract
{
    protected bool $requiresAdminForManagement = true;
}
