<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\Messaging;

use App\Models\Messaging\Message;
use Polis\Events\Messaging\MessageSentEvent;
use Polis\Tests\TestCase;

/**
 * Class MessageSentEventTest
 */
final class MessageSentEventTest extends TestCase
{
    public function test_get_message(): void
    {
        $message = new Message;

        $event = new MessageSentEvent($message);
        $this->assertEquals($message, $event->getMessage());
    }
}
