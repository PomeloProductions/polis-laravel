<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Messaging;

use App\Models\Messaging\Message;
use Polis\Contracts\Models\Messaging\CanReceiveMessageContract;

interface BaseMessageSendingServiceContract
{
    /**
     * Attempts to send a message to the receiver
     */
    public function sendMessage(CanReceiveMessageContract $receiver, Message $message): bool;
}
