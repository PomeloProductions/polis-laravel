<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\User\InvitationToken.
 *
 * Extends BaseModelAbstract because InvitationTokenRepositoryContract::update()
 * and ::delete() type-hint BaseModelAbstract. See Category.php for the
 * shared rationale.
 */
class InvitationToken extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\User\InvitationToken::class, false)) {
    class_alias(
        InvitationToken::class,
        \App\Models\User\InvitationToken::class,
    );
}
