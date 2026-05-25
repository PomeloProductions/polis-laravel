<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\Messaging;

use App\Models\Messaging\Message;
use Carbon\Carbon;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\Messaging\MessageSentEvent;
use Polis\Listeners\Messaging\MessageSentListener;
use Polis\Tests\TestCase;

/**
 * Class MessageSentListenerTest
 */
final class MessageSentListenerTest extends TestCase
{
    public function test_handle(): void
    {
        $messageRepository = mock(MessageRepositoryContract::class);
        $listener = new MessageSentListener($messageRepository);

        $message = new Message;
        $event = new MessageSentEvent($message);

        $carbon = new Carbon;
        Carbon::setTestNow($carbon);

        $messageRepository->shouldReceive('update')->once()->with($message, ['sent_at' => $carbon]);

        $listener->handle($event);
    }
}
