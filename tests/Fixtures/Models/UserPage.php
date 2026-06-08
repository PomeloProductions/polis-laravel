<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\User\UserPage.
 *
 * UserPagePolicyAbstract / UserPageComponentPolicyAbstract read user_id
 * for ownership and is_required for delete-protection.
 */
class UserPage
{
    public ?int $id = null;

    public ?int $user_id = null;

    public bool $is_required = false;
}

if (! class_exists(\App\Models\User\UserPage::class, false)) {
    class_alias(
        UserPage::class,
        \App\Models\User\UserPage::class,
    );
}
