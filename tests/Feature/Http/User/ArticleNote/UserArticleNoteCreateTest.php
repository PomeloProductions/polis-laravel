<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\User\ArticleNote;

use App\Models\User\ArticleNote;
use App\Models\User\User;
use App\Models\Wiki\Article;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserArticleNoteCreateTest
 */
final class UserArticleNoteCreateTest extends ApplicationTestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        $this->user = User::factory()->create();

        $this->path .= $this->user->id.'/article-notes';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_different_user_blocked(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actingAs($this->user);

        $article = Article::factory()->create();

        $response = $this->json('POST', $this->path, [
            'article_id' => $article->id,
            'response' => 'My note',
        ]);

        $response->assertStatus(201);

        /** @var ArticleNote $articleNote */
        $articleNote = ArticleNote::first();
        $this->assertEquals($this->user->id, $articleNote->user_id);
        $this->assertEquals($article->id, $articleNote->article_id);
        $this->assertEquals('My note', $articleNote->response);
        $this->assertNull($articleNote->completed_at);
    }

    public function test_create_successful_with_completed(): void
    {
        $this->actingAs($this->user);

        $article = Article::factory()->create();

        $response = $this->json('POST', $this->path, [
            'article_id' => $article->id,
            'completed' => true,
        ]);

        $response->assertStatus(201);

        /** @var ArticleNote $articleNote */
        $articleNote = ArticleNote::first();
        $this->assertNotNull($articleNote->completed_at);
    }

    public function test_create_fails_missing_required_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'article_id' => ['The article id field is required.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_boolean_fields(): void
    {
        $this->actingAs($this->user);

        $article = Article::factory()->create();

        $response = $this->json('POST', $this->path, [
            'article_id' => $article->id,
            'completed' => 'not-a-boolean',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'completed' => ['The completed field must be true or false.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_string_fields(): void
    {
        $this->actingAs($this->user);

        $article = Article::factory()->create();

        $response = $this->json('POST', $this->path, [
            'article_id' => $article->id,
            'response' => 12345,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'response' => ['The response must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_integer_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'article_id' => 'not-an-integer',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'article_id' => ['The article id must be an integer.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_model_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'article_id' => 99999,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'article_id' => ['The selected article id is invalid.'],
            ],
        ]);
    }
}
