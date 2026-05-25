<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Wiki\ArticleSummary;

use App\Models\User\User;
use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleSummary;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ArticleSummaryViewTest
 */
final class ArticleSummaryViewTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

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
        $response = $this->json('GET', $this->path);

        $response->assertStatus(403);
    }

    public function test_view_successful(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $summary = ArticleSummary::factory()->create([
            'article_id' => $this->article->id,
            'content' => 'Test summary content',
        ]);

        $response = $this->json('GET', $this->path);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $summary->id,
            'article_id' => $this->article->id,
            'content' => 'Test summary content',
        ]);
    }

    public function test_view_not_found(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('GET', $this->path);

        $response->assertStatus(404);
    }
}
