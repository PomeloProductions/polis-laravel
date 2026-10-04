<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Models\User;

use App\Models\Messaging\Message;
use App\Models\Messaging\Thread;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Polis\Contracts\Repositories\Messaging\ThreadRepositoryContract;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class ThreadTest
 */
final class ThreadTest extends ApplicationTestCase
{
    /**
     * Mute Message model events so the factory does not try to actually send
     * notifications in these unit-ish model tests.
     */
    private function muteMessageEvents(): void
    {
        $messageDispatcher = mock(Dispatcher::class);
        Message::setEventDispatcher($messageDispatcher);
        $messageDispatcher->shouldReceive('dispatch');
        $messageDispatcher->shouldReceive('until');
    }

    public function test_last_message(): void
    {
        $this->muteMessageEvents();

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

    /**
     * Before the fix the `last_message` append loaded the ENTIRE messages
     * relation per thread to read ->first(), so serializing a list of threads
     * scaled with the thread count (N+1). After the fix the index repository
     * (ThreadRepository::findAll) eager-loads `latestMessage`, so the query
     * count is bounded and does not grow with the number of threads.
     *
     * We go through the repository's findAll (the actual index/listing path),
     * NOT a bare Thread::query()->get(), because the eager-loading is scoped to
     * that path rather than forced globally via the model's `$with`.
     */
    public function test_serializing_thread_list_is_bounded_and_does_not_grow_with_count(): void
    {
        $this->muteMessageEvents();

        /** @var ThreadRepositoryContract $repository */
        $repository = $this->app->make(ThreadRepositoryContract::class);

        $makeThread = function (): void {
            /** @var Thread $thread */
            $thread = Thread::factory()->create();
            Message::factory()->create([
                'created_at' => '2018-10-10 12:00:00',
                'thread_id' => $thread->id,
            ]);
            Message::factory()->create([
                'created_at' => '2018-10-11 12:00:00',
                'thread_id' => $thread->id,
            ]);
        };

        $countQueriesForListingOf = function (int $threadCount) use ($makeThread, $repository): int {
            Thread::query()->forceDelete();

            for ($i = 0; $i < $threadCount; $i++) {
                $makeThread();
            }

            DB::flushQueryLog();
            DB::enableQueryLog();

            // Exercise the real index/listing path. Serializing exercises the
            // `last_message` append on every row.
            $repository->findAll(limit: 0)->toArray();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $queriesForOne = $countQueriesForListingOf(1);
        $queriesForMany = $countQueriesForListingOf(5);

        // The per-row N+1 is gone: listing 5 threads costs the same bounded
        // number of queries as listing 1 (NOT 5x).
        $this->assertSame(
            $queriesForOne,
            $queriesForMany,
            'The thread index must not issue more queries as the row count grows (N+1 regression).'
        );

        // Base select + the single eager-loaded latestMessage relation.
        $this->assertLessThanOrEqual(
            3,
            $queriesForMany,
            'The thread index should take a small, bounded number of queries.'
        );
    }

    /**
     * Asserts the N+1 fix is wired on the INDEX path: the index repository
     * eager-loads `latestMessage`, it is hidden from the serialized shape, and
     * feeds the `last_message` append. A bare model load does NOT eager-load it
     * (the relation is intentionally not on the model's `$with`).
     */
    public function test_latest_message_is_eager_loaded_on_index_hidden_and_feeds_the_append(): void
    {
        $this->muteMessageEvents();

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

        // A bare model load must NOT eager-load latestMessage: it is scoped to
        // the index path, not forced globally via `$with`.
        /** @var Thread $bare */
        $bare = Thread::query()->findOrFail($thread->id);
        $this->assertFalse($bare->relationLoaded('latestMessage'));

        // The index path eager-loads it.
        /** @var ThreadRepositoryContract $repository */
        $repository = $this->app->make(ThreadRepositoryContract::class);
        /** @var Thread $fresh */
        $fresh = $repository->findAll(limit: 0)->firstOrFail();

        $this->assertTrue($fresh->relationLoaded('latestMessage'));
        $this->assertSame($newMessage->id, $fresh->latestMessage->id);

        // Hidden from serialization so the JSON shape is unchanged...
        $array = $fresh->toArray();
        $this->assertArrayNotHasKey('latest_message', $array);

        // ...but its value feeds the `last_message` append.
        $this->assertArrayHasKey('last_message', $array);
        $this->assertSame($newMessage->id, $array['last_message']['id']);
    }
}
