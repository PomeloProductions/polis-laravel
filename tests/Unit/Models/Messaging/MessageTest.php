<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Messaging;

use App\Models\Messaging\Message;
use Polis\Tests\TestCase;

/**
 * Class MessageTest
 */
final class MessageTest extends TestCase
{
    public function test_from(): void
    {
        $message = new Message;
        $relation = $message->from();

        $this->assertEquals('messages.from_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('from_type', $relation->getMorphType());
    }

    public function test_thread(): void
    {
        $message = new Message;
        $relation = $message->thread();

        $this->assertEquals('threads.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('messages.thread_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_to(): void
    {
        $message = new Message;
        $relation = $message->to();

        $this->assertEquals('messages.to_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('to_type', $relation->getMorphType());
    }
}
