<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Role.
 *
 * Required because IsAnEntityContract::canUserManageEntity() uses
 * Role::MANAGER as a default argument value, so the constant must exist
 * on the type being aliased. See User.php for the rationale.
 */
class Role
{
    /** Mirror of Polis\Models\Role role constants used in contract signatures. */
    public const APP_USER = 1;

    public const SUPER_ADMIN = 2;

    public const ARTICLE_VIEWER = 3;

    public const ARTICLE_EDITOR = 4;

    public const ADMINISTRATOR = 10;

    public const MANAGER = 11;

    public const CONTENT_EDITOR = 100;

    public const SUPPORT_STAFF = 101;
}

if (! class_exists(\App\Models\Role::class, false)) {
    class_alias(
        Role::class,
        \App\Models\Role::class,
    );
}
