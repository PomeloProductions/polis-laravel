<?php

declare(strict_types=1);

namespace Polis\Contracts\Models;

use Polis\Contracts\Models\Messaging\CanReceiveMessageContract;

/**
 * Interface CanReceiveTextMessagesContract
 */
interface CanReceiveTextMessagesContract extends CanReceiveMessageContract
{
    /**
     * Gets the formatted phone number to send via twilio
     */
    public function routeNotificationForTwilio(): ?string;
}
