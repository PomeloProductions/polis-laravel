<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\Messaging;

use App\Models\Messaging\Message;
use Polis\Events\Messaging\MessageCreatedEvent;
use Polis\Tests\TestCase;

/**
 * Class MessageCreatedEventTest
 */
final class MessageCreatedEventTest extends TestCase
{
    public function test_get_message(): void
    {
        $message = new Message;

        $event = new MessageCreatedEvent($message);
        $this->assertEquals($message, $event->getMessage());
    }
}
