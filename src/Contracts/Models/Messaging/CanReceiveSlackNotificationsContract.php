<?php

declare(strict_types=1);

namespace Polis\Contracts\Models\Messaging;

use App\Models\Messaging\Message;

interface CanReceiveSlackNotificationsContract extends CanReceiveMessageContract
{
    /**
     * Gets the key used to validate access to the related slack workspace
     */
    public function getSlackKey(Message $message): ?string;

    /**
     * Gets the slack channel name based on the message passed in
     */
    public function getSlackChannel(Message $message): ?string;
}
