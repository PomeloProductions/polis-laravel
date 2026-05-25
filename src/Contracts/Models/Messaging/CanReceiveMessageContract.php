<?php

declare(strict_types=1);

namespace Polis\Contracts\Models\Messaging;

use App\Models\Messaging\Message;
use Polis\Contracts\Models\CanBeMorphedToContract;

/**
 * @property $id The primary id of this model
 */
interface CanReceiveMessageContract extends CanBeMorphedToContract
{
    /**
     * This will return if the message can be received by the specific model
     */
    public function canReceiveMessage(Message $message): bool;
}
