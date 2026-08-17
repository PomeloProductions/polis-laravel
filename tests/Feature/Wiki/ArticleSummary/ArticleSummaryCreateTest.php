<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Wiki\ArticleSummary;

use App\Models\Role;
use App\Models\User\User;
use App\Models\Wiki\Article;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ArticleSummaryCreateTest
 */
final class ArticleSummaryCreateTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private string $path;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        $this->article = Article::factory()->create();
        $this->path = '/v1/articles/'.$this->article->id.'/article-summary';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_user_without_role_blocked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path, [
            'content' => 'Test summary content',
        ]);

        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::ARTICLE_EDITOR);
        $this->actingAs($user);

        $response = $this->json('POST', $this->path, [
            'content' => 'This is a test summary for the article.',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'article_id' => $this->article->id,
            'content' => 'This is a test summary for the article.',
        ]);
    }

    public function test_create_fails_missing_required_fields(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::ARTICLE_EDITOR);
        $this->actingAs($user);

        $response = $this->json('POST', $this->path, []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_create_fails_invalid_string_fields(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::ARTICLE_EDITOR);
        $this->actingAs($user);

        $response = $this->json('POST', $this->path, [
            'content' => 12345,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }
}
