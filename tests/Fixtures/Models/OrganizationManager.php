<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Organization\OrganizationManager.
 *
 * The OrganizationManagerPolicyAbstract type-hints this class on update()
 * and delete() to validate that the manager belongs to the supplied
 * organization. See tests/Fixtures/Models/User.php for the rationale.
 */
class OrganizationManager
{
    public ?int $id = null;

    public ?int $organization_id = null;

    public ?int $user_id = null;
}

if (! class_exists(\App\Models\Organization\OrganizationManager::class, false)) {
    class_alias(
        OrganizationManager::class,
        \App\Models\Organization\OrganizationManager::class,
    );
}
