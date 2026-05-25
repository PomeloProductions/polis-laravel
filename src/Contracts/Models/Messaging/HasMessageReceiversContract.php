<?php

declare(strict_types=1);

namespace Polis\Contracts\Models\Messaging;

use App\Models\Messaging\Message;
use Illuminate\Support\Collection;

/**
 * @property CanReceiveMessageContract[]|Collection $messageReceivers
 */
interface HasMessageReceiversContract
{
    /**
     * All message receivers contained within this model
     * These related models will be used to send messages when the parent does not
     *
     * @param  Message  $message  The message being sent in case there is only
     *                            logic connected to returning receivers
     * @return Collection<CanReceiveMessageContract>
     */
    public function messageReceivers(Message $message): Collection;
}
