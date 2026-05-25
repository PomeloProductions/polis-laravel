<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Wiki\ArticleSummary;

use App\Models\Role;
use App\Models\User\User;
use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleSummary;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ArticleSummaryDeleteTest
 */
final class ArticleSummaryDeleteTest extends TestCase
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
        ArticleSummary::factory()->create([
            'article_id' => $this->article->id,
        ]);

        $response = $this->json('DELETE', $this->path);

        $response->assertStatus(403);
    }

    public function test_user_without_role_blocked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        ArticleSummary::factory()->create([
            'article_id' => $this->article->id,
        ]);

        $response = $this->json('DELETE', $this->path);

        $response->assertStatus(403);
    }

    public function test_not_found(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::ARTICLE_EDITOR);
        $this->actingAs($user);

        $response = $this->json('DELETE', $this->path);

        $response->assertStatus(404);
    }

    public function test_delete_successful(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::ARTICLE_EDITOR);
        $this->actingAs($user);

        $summary = ArticleSummary::factory()->create([
            'article_id' => $this->article->id,
        ]);

        $response = $this->json('DELETE', $this->path);

        $response->assertStatus(204);

        // Verify the summary was soft-deleted
        $this->assertNull(ArticleSummary::find($summary->id));
    }
}
