<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Policies;

use Polis\Policies\BaseBelongsToOrganizationPolicyAbstract;

/**
 * Empty concrete subclass for BaseBelongsToOrganizationPolicyAbstract.
 * Used to exercise the default ($requiresAdminForManagement = false)
 * branch of the create/update/delete gates.
 */
class BaseBelongsToOrganizationPolicy extends BaseBelongsToOrganizationPolicyAbstract {}
