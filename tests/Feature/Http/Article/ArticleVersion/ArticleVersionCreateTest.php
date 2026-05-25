<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Article\ArticleVersion;

use App\Models\Role;
use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use App\Models\Wiki\ArticleVersion;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ArticleVersionCreateTest
 */
final class ArticleVersionCreateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/articles/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $article = Article::factory()->create();
        $response = $this->json('POST', $this->path.$article->id.'/versions');

        $response->assertStatus(403);
    }

    public function test_non_owning_user_blocked(): void
    {
        $this->actAs(Role::ARTICLE_EDITOR);
        $article = Article::factory()->create();
        $response = $this->json('POST', $this->path.$article->id.'/versions');

        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actAs(Role::ARTICLE_EDITOR);

        $article = Article::factory()->create([
            'created_by_id' => $this->actingAs->id,
        ]);
        $iteration = ArticleIteration::factory()->create([
            'article_id' => $article->id,
        ]);

        $response = $this->json('POST', $this->path.$article->id.'/versions', [
            'article_iteration_id' => $iteration->id,
        ]);

        $response->assertStatus(201);

        $articleVersion = ArticleVersion::first();
        $this->assertEquals($articleVersion->article_iteration_id, $iteration->id);
    }

    public function test_create_invalid_integer_fields(): void
    {
        $this->actAs(Role::ARTICLE_EDITOR);

        $article = Article::factory()->create([
            'created_by_id' => $this->actingAs->id,
        ]);

        $response = $this->json('POST', $this->path.$article->id.'/versions', [
            'article_iteration_id' => 'hi',
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'article_iteration_id' => ['The article iteration id must be an integer.'],
            ],
        ]);
    }

    public function test_create_invalid_model_fields(): void
    {
        $this->actAs(Role::ARTICLE_EDITOR);

        $article = Article::factory()->create([
            'created_by_id' => $this->actingAs->id,
        ]);

        $response = $this->json('POST', $this->path.$article->id.'/versions', [
            'article_iteration_id' => 245,
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'article_iteration_id' => ['The selected article iteration id is invalid.'],
            ],
        ]);
    }

    public function test_create_fails_iteration_not_from_article(): void
    {
        $this->actAs(Role::ARTICLE_EDITOR);

        $article = Article::factory()->create([
            'created_by_id' => $this->actingAs->id,
        ]);
        $iteration = ArticleIteration::factory()->create();

        $response = $this->json('POST', $this->path.$article->id.'/versions', [
            'article_iteration_id' => $iteration->id,
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'article_iteration_id' => ['The selected article iteration id does not seem to be from the related article.'],
            ],
        ]);
    }
}
