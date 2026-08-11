<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\User\Thread\Message;

use App\Models\Messaging\Message;
use App\Models\Messaging\Thread;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserThreadMessageCreateTest
 */
final class UserThreadMessageCreateTest extends ApplicationTestCase
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

        $this->path .= $this->user->id.'/threads/'.$this->thread->id.'/messages';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actingAs($this->user);

        $this->thread->users()->sync([$this->user->id]);

        Message::flushEventListeners();

        $response = $this->json('POST', $this->path, [
            'message' => 'A Message',
        ]);

        $response->assertStatus(201);

        /** @var Message $message */
        $message = Message::first();

        $this->assertEquals('A Message', $message->data['body']);
        $this->assertEquals($this->user->id, $message->from_id);
    }

    public function test_create_invalid_string_fields(): void
    {
        $this->actingAs($this->user);

        $this->thread->users()->sync([$this->user->id]);

        $response = $this->json('POST', $this->path, [
            'message' => 3543,
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'message' => ['The message must be a string.'],
            ],
        ]);
    }
}
