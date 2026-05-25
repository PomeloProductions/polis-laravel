<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\Messaging;

use App\Models\Messaging\Message;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Contracts\Services\Messaging\MessageSendingSelectionServiceContract;
use Polis\Contracts\Services\Messaging\SendSlackNotificationServiceContract;
use Polis\Events\Messaging\MessageCreatedEvent;
use Polis\Listeners\Messaging\MessageCreatedListener;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class MessageCreatedListenerTest
 */
final class MessageCreatedListenerTest extends TestCase
{
    /**
     * @var MessageSendingSelectionServiceContract|(MessageSendingSelectionServiceContract&MockInterface&LegacyMockInterface)|(MessageSendingSelectionServiceContract&CustomMockInterface)|array|(MockInterface&LegacyMockInterface)|CustomMockInterface
     */
    private $messageSendingSelectionService;

    /**
     * @var MessageRepositoryContract|(MessageRepositoryContract&MockInterface&LegacyMockInterface)|(MessageRepositoryContract&CustomMockInterface)|array|(MockInterface&LegacyMockInterface)|CustomMockInterface
     */
    private $messageRepository;

    /**
     * @var array|Dispatcher|(Dispatcher&MockInterface&LegacyMockInterface)|(Dispatcher&CustomMockInterface)|(MockInterface&LegacyMockInterface)|CustomMockInterface
     */
    private $events;

    private MessageCreatedListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageSendingSelectionService = mock(MessageSendingSelectionServiceContract::class);
        $this->messageRepository = mock(MessageRepositoryContract::class);
        $this->events = mock(Dispatcher::class);
        $this->listener = new MessageCreatedListener(
            $this->messageSendingSelectionService,
            $this->messageRepository,
            $this->events,
        );
    }

    public function test_handle_does_nothing_when_sending_service_is_not_configured()
    {
        $message = new Message([
            'via' => Message::VIA_SLACK,
        ]);
        $event = new MessageCreatedEvent($message);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->messageRepository->shouldReceive('update')->with($message, [
            'scheduled_at' => $now,
        ]);
        $this->messageSendingSelectionService
            ->shouldReceive('getSendingService')
            ->with(Message::VIA_SLACK)
            ->andReturn(null);

        $this->listener->handle($event);
    }

    public function test_handle_does_nothing_with_no_valid_senders()
    {
        $message = new Message([
            'via' => Message::VIA_SLACK,
        ]);
        $event = new MessageCreatedEvent($message);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $slackService = mock(SendSlackNotificationServiceContract::class);

        $this->messageRepository->shouldReceive('update')->with($message, [
            'scheduled_at' => $now,
        ]);
        $this->messageSendingSelectionService
            ->shouldReceive('getSendingService')
            ->with(Message::VIA_SLACK)
            ->andReturn($slackService);

        $this->listener->handle($event);
    }

    public function test_handle_sends_to_single_receiver()
    {
        $message = new Message([
            'via' => Message::VIA_SLACK,
            'to' => new User,
        ]);
        $event = new MessageCreatedEvent($message);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $slackService = mock(SendSlackNotificationServiceContract::class);
        $slackService->shouldReceive('sendMessage')->with($message->to, $message);

        $this->messageRepository->shouldReceive('update')->with($message, [
            'scheduled_at' => $now,
        ]);
        $this->messageSendingSelectionService
            ->shouldReceive('getSendingService')
            ->with(Message::VIA_SLACK)
            ->andReturn($slackService);
        $this->events->shouldReceive('dispatch');

        $this->listener->handle($event);
    }

    public function test_handle_sends_to_child_receivers()
    {
        $message = new Message([
            'via' => Message::VIA_SLACK,
            'to' => new Organization([
                'organizationManagers' => collect([
                    new OrganizationManager([
                        'user' => new User([
                            'id' => 43,
                        ]),
                    ]),
                    new OrganizationManager([
                        'user' => new User([
                            'id' => 7,
                        ]),
                    ]),
                ]),
            ]),
        ]);
        $event = new MessageCreatedEvent($message);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $slackService = mock(SendSlackNotificationServiceContract::class);
        $slackService->shouldReceive('sendMessage')
            ->with($message->to, $message);
        $slackService->shouldReceive('sendMessage')
            ->with($message->to->organizationManagers[0]->user, $message);
        $slackService->shouldReceive('sendMessage')
            ->with($message->to->organizationManagers[1]->user, $message);

        $this->messageRepository->shouldReceive('update')->with($message, [
            'scheduled_at' => $now,
        ]);
        $this->messageSendingSelectionService
            ->shouldReceive('getSendingService')
            ->with(Message::VIA_SLACK)
            ->andReturn($slackService);
        $this->events->shouldReceive('dispatch');

        $this->listener->handle($event);
    }
}
