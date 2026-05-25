<?php

declare(strict_types=1);

namespace Polis\Listeners\Messaging;

use App\Models\Messaging\Message;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Polis\Contracts\Models\Messaging\CanReceiveMessageContract;
use Polis\Contracts\Models\Messaging\HasMessageReceiversContract;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Contracts\Services\Messaging\BaseMessageSendingServiceContract;
use Polis\Contracts\Services\Messaging\MessageSendingSelectionServiceContract;
use Polis\Events\Messaging\MessageCreatedEvent;
use Polis\Events\Messaging\MessageSentEvent;

/**
 * Class MessageCreatedListener
 */
class MessageCreatedListener implements ShouldQueue
{
    /**
     * MessageCreatedListener constructor.
     */
    public function __construct(
        private MessageSendingSelectionServiceContract $messageSendingSelectionService,
        private MessageRepositoryContract $messageRepository,
        private Dispatcher $events,
    ) {}

    /**
     * Schedules the message to be sent
     *
     * @throws Exception
     */
    public function handle(MessageCreatedEvent $event): void
    {
        $message = $event->getMessage();

        $this->messageRepository->update($message, [
            'scheduled_at' => Carbon::now(),
        ]);

        $channels = collect($message->via ?? [Message::VIA_EMAIL]);

        $sent = false;

        $availableServices =
            $channels->map(fn (string $via) => $this->messageSendingSelectionService->getSendingService($via))
                ->filter(fn (?BaseMessageSendingServiceContract $maybeService) => $maybeService);
        foreach ($availableServices as $service) {

            $to = $message->to;
            if ($to instanceof CanReceiveMessageContract) {
                $service->sendMessage($to, $message);
                $sent = true;
            }
            if ($to instanceof HasMessageReceiversContract) {
                foreach ($to->messageReceivers($message) as $messageReceiver) {
                    $service->sendMessage($messageReceiver, $message);
                    $sent = true;
                }
            }
        }

        if ($sent) {
            $this->events->dispatch(new MessageSentEvent($message));
        }
    }
}
