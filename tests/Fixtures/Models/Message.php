<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Messaging\Message.
 *
 * Required because MessageRepositoryContract methods reference
 * App\Models\Messaging\Message as both parameter and return type. The
 * contract also uses Message::VIA_EMAIL as a default argument, and the
 * Thread\MessageControllerAbstract::store uses Message::VIA_PUSH_NOTIFICATION.
 *
 * Extends BaseModelAbstract so it can pass through the repository's
 * update() (Thread\MessageController::update calls it).
 */
class Message extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;

    /** Mirror of Polis\Models\Messaging\Message::VIA_EMAIL. */
    public const VIA_EMAIL = 'email';

    /** Mirror of Polis\Models\Messaging\Message::VIA_PUSH_NOTIFICATION. */
    public const VIA_PUSH_NOTIFICATION = 'push';

    /** Mirror of Polis\Models\Messaging\Message::VIA_SMS for sendTextMessage. */
    public const VIA_SMS = 'sms';
}

if (! class_exists(\App\Models\Messaging\Message::class, false)) {
    class_alias(
        Message::class,
        \App\Models\Messaging\Message::class,
    );
}
