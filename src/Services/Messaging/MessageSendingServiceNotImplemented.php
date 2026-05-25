<?php

declare(strict_types=1);

namespace Polis\Services\Messaging;

use App\Models\Messaging\Message;
use Polis\Contracts\Models\Messaging\CanReceiveMessageContract;
use Polis\Contracts\Services\Messaging\BaseMessageSendingServiceContract;
use Polis\Exceptions\NotImplementedException;

class MessageSendingServiceNotImplemented implements BaseMessageSendingServiceContract
{
    /**
     * Attempts to send a message to the receiver
     */
    public function sendMessage(CanReceiveMessageContract $receiver, Message $message): bool
    {
        throw new NotImplementedException('This messaging channel is not currently implemented. There probably needs to be additional configuration to use this channel.');
    }
}
