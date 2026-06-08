<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Organization\OrganizationManager.
 *
 * Extends BaseModelAbstract so it can pass through
 * OrganizationManagerRepositoryContract::update() and ::delete(). See
 * Category.php for the shared rationale.
 */
class OrganizationManager extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Organization\OrganizationManager::class, false)) {
    class_alias(
        OrganizationManager::class,
        \App\Models\Organization\OrganizationManager::class,
    );
}
