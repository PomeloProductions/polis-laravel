<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Organization\Organization.
 *
 * Extends BaseModelAbstract because OrganizationRepositoryContract::update()
 * and ::delete() type-hint BaseModelAbstract, and the controller calls
 * $org->load() in show(). See Category.php for the shared rationale.
 */
class Organization extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Organization\Organization::class, false)) {
    class_alias(
        Organization::class,
        \App\Models\Organization\Organization::class,
    );
}
