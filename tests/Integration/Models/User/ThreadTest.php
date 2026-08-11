<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Models\User;

use App\Models\Messaging\Message;
use App\Models\Messaging\Thread;
use Illuminate\Contracts\Events\Dispatcher;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class ThreadTest
 */
final class ThreadTest extends ApplicationTestCase
{
    
    public function test_last_message(): void
    {
        $messageDispatcher = mock(Dispatcher::class);
        Message::setEventDispatcher($messageDispatcher);
        $messageDispatcher->shouldReceive('dispatch');
        $messageDispatcher->shouldReceive('until');

        /** @var Thread $thread */
        $thread = Thread::factory()->create();
        Message::factory()->create([
            'created_at' => '2018-10-10 12:00:00',
            'thread_id' => $thread->id,
        ]);
        $newMessage = Message::factory()->create([
            'created_at' => '2018-10-11 12:00:00',
            'thread_id' => $thread->id,
        ]);

        $this->assertEquals($thread->last_message->id, $newMessage->id);
    }
}
