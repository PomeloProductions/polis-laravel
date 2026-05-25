<?php

declare(strict_types=1);

namespace Polis\Listeners\Messaging;

use Carbon\Carbon;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\Messaging\MessageSentEvent;

/**
 * Class MessageSentListener
 */
class MessageSentListener
{
    /**
     * @var MessageRepositoryContract
     */
    private $messageRepository;

    /**
     * MessageSentListener constructor.
     */
    public function __construct(MessageRepositoryContract $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * Sets the sent at field to the message
     */
    public function handle(MessageSentEvent $event)
    {
        $message = $event->getMessage();

        $this->messageRepository->update($message, [
            'sent_at' => Carbon::now(),
        ]);
    }
}
