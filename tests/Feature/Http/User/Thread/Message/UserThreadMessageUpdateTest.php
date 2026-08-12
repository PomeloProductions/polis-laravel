<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\User\Thread\Message;

use App\Models\Messaging\Message;
use App\Models\Messaging\Thread;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserThreadMessageUpdateTest
 */
final class UserThreadMessageUpdateTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users/';

    /**
     * @var User
     */
    private $user;

    /**
     * @var Thread
     */
    private $thread;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        $this->user = User::factory()->create();
        $this->thread = Thread::factory()->create([
            'subject_type' => 'private_message',
        ]);

        $this->path .= $this->user->id.'/threads/'.$this->thread->id.'/messages/';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        Message::unsetEventDispatcher();
        $message = Message::factory()->create();
        $response = $this->json('PUT', $this->path.$message->id);

        $response->assertStatus(403);
    }

    public function test_update_successful(): void
    {
        Message::unsetEventDispatcher();
        $this->actingAs($this->user);

        $this->thread->users()->sync([$this->user->id]);
        $message = Message::factory()->create([
            'to_id' => $this->user->id,
            'thread_id' => $this->thread->id,
        ]);

        Message::flushEventListeners();

        $response = $this->json('PUT', $this->path.$message->id, [
            'seen' => true,
        ]);

        $response->assertStatus(200);

        /** @var Message $message */
        $message = Message::first();

        $this->assertNotNull($message->seen_at);
    }

    public function test_update_fails_invalid_boolean_fields(): void
    {
        Message::unsetEventDispatcher();
        $this->actingAs($this->user);

        $this->thread->users()->sync([$this->user->id]);

        $message = Message::factory()->create([
            'to_id' => $this->user->id,
            'thread_id' => $this->thread->id,
        ]);

        $response = $this->json('PUT', $this->path.$message->id, [
            'seen' => 'hi',
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'seen' => ['The seen field must be true or false.'],
            ],
        ]);
    }
}
