<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\User\Thread;

use App\Models\Messaging\Thread;
use App\Models\User\User;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserThreadCreateTest
 */
final class UserThreadCreateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users/';

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        $this->user = User::factory()->create();

        $this->path .= $this->user->id.'/threads';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        /** @var Thread $thread */
        $thread = Thread::factory()->create();
        $thread->users()->attach($otherUser->id);
        $thread->users()->attach($this->user->id);

        $user = User::factory()->create();

        $response = $this->json('POST', $this->path, [
            'subject_type' => 'private_message',
            'users' => [$user->id],
        ]);

        $response->assertStatus(201);

        /** @var Thread $thread */
        $thread = Thread::all()[1];
        $this->assertCount(2, $thread->users);

        $this->assertTrue($thread->users->contains($this->user->id));
        $this->assertTrue($thread->users->contains($user->id));
    }

    public function test_create_invalid_array_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'subject_type' => 'private_message',
            'users' => 'hi',
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'users' => ['The users must be an array.'],
            ],
        ]);
    }

    public function test_create_invalid_integer_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'subject_type' => 'private_message',
            'subject_id' => 'hi',
            'users' => ['hi'],
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'subject_id' => ['The subject id must be an integer.'],
                'users.0' => ['The users.0 must be an integer.'],
            ],
        ]);
    }

    public function test_create_invalid_model_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'subject_type' => 'private_message',
            'users' => [546],
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'users.0' => ['The selected users.0 is invalid.'],
            ],
        ]);
    }
}
