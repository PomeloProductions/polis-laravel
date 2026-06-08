<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\User\UserPageComponent.
 *
 * Extends BaseModelAbstract because UserPageComponentRepositoryContract's
 * update()/delete() type-hint BaseModelAbstract. See Category.php for the
 * shared rationale.
 */
class UserPageComponent extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\User\UserPageComponent::class, false)) {
    class_alias(
        UserPageComponent::class,
        \App\Models\User\UserPageComponent::class,
    );
}
