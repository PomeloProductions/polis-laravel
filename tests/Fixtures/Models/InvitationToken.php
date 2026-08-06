<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\User\InvitationToken as PolisInvitationToken;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\User\InvitationToken.
 *
 * Extends the package InvitationToken model (which itself extends
 * BaseModelAbstract) so it satisfies both the BaseModelAbstract type-hints on
 * InvitationTokenRepositoryContract::update()/::delete() AND the
 * Polis\Models\User\InvitationToken type-hint on OrganizationManagerCreatedEvent
 * / InvitationAcceptedEvent. In a real consumer app,
 * App\Models\User\InvitationToken likewise subclasses the package model.
 */
class InvitationToken extends PolisInvitationToken
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\User\InvitationToken::class, false)) {
    class_alias(
        InvitationToken::class,
        \App\Models\User\InvitationToken::class,
    );
}
