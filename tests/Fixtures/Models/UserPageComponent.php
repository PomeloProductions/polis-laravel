<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\User\UserPageComponent.
 *
 * UserPageComponentPolicyAbstract reads $user_page_id to validate the
 * component belongs to the named page.
 */
class UserPageComponent
{
    public ?int $id = null;

    public ?int $user_page_id = null;
}

if (! class_exists(\App\Models\User\UserPageComponent::class, false)) {
    class_alias(
        UserPageComponent::class,
        \App\Models\User\UserPageComponent::class,
    );
}
