<?php

declare(strict_types=1);

namespace Polis\Events\Messaging;

use App\Models\Messaging\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class MessageSentEvent
 */
class MessageSentEvent implements ShouldQueue
{
    use Queueable;

    /**
     * @var Message
     */
    private $message;

    /**
     * MessageSentEvent constructor.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
