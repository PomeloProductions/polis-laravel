<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\User\InvitationToken.
 *
 * The InvitationTokenPolicyAbstract returns false for every gate at the
 * abstract level (only super admins can pass via before()). The fixture
 * only needs to satisfy the type hint.
 */
class InvitationToken
{
    public ?int $id = null;
}

if (! class_exists(\App\Models\User\InvitationToken::class, false)) {
    class_alias(
        InvitationToken::class,
        \App\Models\User\InvitationToken::class,
    );
}
