<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\User\UserPage.
 *
 * Extends BaseModelAbstract because UserPageRepositoryContract::update()
 * and ::delete() type-hint BaseModelAbstract. See Category.php for the
 * shared rationale.
 */
class UserPage extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\User\UserPage::class, false)) {
    class_alias(
        UserPage::class,
        \App\Models\User\UserPage::class,
    );
}
