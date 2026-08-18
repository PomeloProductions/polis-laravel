<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\User\ArticleNote;

use App\Models\User\ArticleNote;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserArticleNoteUpdateTest
 */
final class UserArticleNoteUpdateTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users/';

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        $this->user = User::factory()->create();

        $this->path .= $this->user->id.'/article-notes/';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $articleNote = ArticleNote::factory()->create();

        $response = $this->json('PUT', $this->path.$articleNote->id);

        $response->assertStatus(403);
    }

    public function test_different_user_blocked(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $articleNote = ArticleNote::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PUT', $this->path.$articleNote->id);

        $response->assertStatus(403);
    }

    public function test_not_found(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('PUT', $this->path.'453');

        $response->assertStatus(404);
    }

    public function test_update_successful(): void
    {
        $this->actingAs($this->user);

        $articleNote = ArticleNote::factory()->create([
            'user_id' => $this->user->id,
            'response' => 'Original response',
            'completed_at' => null,
        ]);

        $response = $this->json('PUT', $this->path.$articleNote->id, [
            'response' => 'Updated response',
        ]);

        $response->assertStatus(200);

        /** @var ArticleNote $updated */
        $updated = ArticleNote::find($articleNote->id);

        $this->assertEquals('Updated response', $updated->response);
    }

    public function test_update_successful_mark_completed(): void
    {
        $this->actingAs($this->user);

        $articleNote = ArticleNote::factory()->create([
            'user_id' => $this->user->id,
            'completed_at' => null,
        ]);

        $response = $this->json('PUT', $this->path.$articleNote->id, [
            'completed' => true,
        ]);

        $response->assertStatus(200);

        /** @var ArticleNote $updated */
        $updated = ArticleNote::find($articleNote->id);

        $this->assertNotNull($updated->completed_at);
    }

    public function test_update_successful_unmark_completed(): void
    {
        $this->actingAs($this->user);

        $articleNote = ArticleNote::factory()->create([
            'user_id' => $this->user->id,
            'completed_at' => now(),
        ]);

        $response = $this->json('PUT', $this->path.$articleNote->id, [
            'completed' => false,
        ]);

        $response->assertStatus(200);

        /** @var ArticleNote $updated */
        $updated = ArticleNote::find($articleNote->id);

        $this->assertNull($updated->completed_at);
    }

    public function test_update_fails_invalid_boolean_fields(): void
    {
        $this->actingAs($this->user);

        $articleNote = ArticleNote::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PUT', $this->path.$articleNote->id, [
            'completed' => 'not-a-boolean',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'completed' => ['The completed field must be true or false.'],
            ],
        ]);
    }

    public function test_update_fails_invalid_string_fields(): void
    {
        $this->actingAs($this->user);

        $articleNote = ArticleNote::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PUT', $this->path.$articleNote->id, [
            'response' => 12345,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'response' => ['The response must be a string.'],
            ],
        ]);
    }
}
