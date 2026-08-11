<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Models\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use App\Models\Wiki\ArticleVersion;
use Carbon\Carbon;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ArticleTest
 */
final class ArticleTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
    }

    public function test_content_returns_null(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        $this->assertNull($article->content);
    }

    public function test_current_version_returns_proper_version(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        ArticleVersion::factory()->create([
            'article_id' => $article->id,
        ]);

        $expected = ArticleVersion::factory()->create([
            'article_id' => $article->id,
        ]);

        $this->assertEquals($expected->id, $article->current_version->id);
    }

    public function test_content_returns_model_content(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        /** @var ArticleIteration $iteration This should be appended */
        $iteration = ArticleIteration::factory()->create([
            'article_id' => $article->id,
            'content' => 'Hello',
        ]);

        ArticleVersion::factory()->create([
            'article_id' => $article->id,
            'article_iteration_id' => $iteration->id,
        ]);

        $this->assertEquals('Hello', $article->content);
    }

    public function test_content_returns_correct_model(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        /** This should be appended */
        $iteration = ArticleIteration::factory()->create([
            'article_id' => $article->id,
            'created_at' => Carbon::now(),
            'content' => 'Hello',
        ]);

        ArticleVersion::factory()->create([
            'article_id' => $article->id,
            'article_iteration_id' => $iteration->id,
        ]);

        /** This is an old iteration that should not be appended */
        $iteration = ArticleIteration::factory()->create([
            'article_id' => $article->id,
            'content' => 'old content',
        ]);

        ArticleVersion::factory()->create([
            'article_id' => $article->id,
            'article_iteration_id' => $iteration->id,
            'created_at' => Carbon::now()->subDay(),
        ]);

        $this->assertEquals('Hello', $article->content);
    }

    public function test_last_iteration_content_returns_model_content(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        /** @var ArticleIteration $iteration This should be appended */
        ArticleIteration::factory()->create([
            'article_id' => $article->id,
            'content' => 'Hello',
        ]);

        $this->assertEquals('Hello', $article->last_iteration_content);
    }

    public function test_last_iteration_content_returns_correct_model(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        /** This should be appended */
        ArticleIteration::factory()->create([
            'article_id' => $article->id,
            'created_at' => Carbon::now(),
            'content' => 'Hello',
        ]);

        /** This is an old iteration that should not be appended */
        ArticleIteration::factory()->create([
            'article_id' => $article->id,
            'created_at' => Carbon::now()->subDay(),
            'content' => 'old content',
        ]);

        $this->assertEquals('Hello', $article->last_iteration_content);
    }
}
