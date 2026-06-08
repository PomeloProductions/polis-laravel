<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Organization\Organization.
 *
 * Policy abstracts type-hint App\Models\Organization\Organization in their
 * gate-method signatures. This minimal stub lets the policy tests assign
 * organization-shaped objects without pulling in any consumer-app code.
 * See tests/Fixtures/Models/User.php for the broader rationale.
 */
class Organization
{
    public ?int $id = null;
}

if (! class_exists(\App\Models\Organization\Organization::class, false)) {
    class_alias(
        Organization::class,
        \App\Models\Organization\Organization::class,
    );
}
