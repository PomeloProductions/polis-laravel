<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Messaging\Message.
 *
 * Required because MessageRepositoryContract methods reference
 * App\Models\Messaging\Message as both parameter and return type, and the
 * contract also uses the Message::VIA_EMAIL class constant as a default
 * argument. See User.php for the rationale.
 */
class Message
{
    /**
     * Mirror of Polis\Models\Messaging\Message::VIA_EMAIL.
     *
     * The constant is referenced as a default argument value in
     * MessageRepositoryContract::sendEmailToUser(); reflection of that
     * signature requires the constant to exist on the type being aliased.
     */
    public const VIA_EMAIL = 'email';
}

if (! class_exists(\App\Models\Messaging\Message::class, false)) {
    class_alias(
        Message::class,
        \App\Models\Messaging\Message::class,
    );
}
