<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Models\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use App\Models\Wiki\ArticleVersion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Polis\Contracts\Repositories\Wiki\ArticleRepositoryContract;
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

        // latestVersion was not eager-loaded on this instance, so the accessor
        // falls back to the original lazy versions() query and still resolves
        // the newest version.
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

        // latestVersion/latestIteration not eager-loaded here: accessor lazy
        // fallback still resolves the content.
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

    /**
     * Each of these articles has a current version (+ its iteration) and a
     * latest iteration that the `content` / `last_iteration_content` appends
     * read. Before the fix those appends lazy-loaded per article (~3 queries
     * each), so serializing a list of N articles scaled with N. After the fix
     * the index repository (ArticleRepository::findAll) eager-loads the latest
     * version/iteration relations, so the query count is bounded and does not
     * grow with the number of articles.
     *
     * We go through the repository's findAll (the actual index/listing path),
     * NOT a bare Article::query()->get(), because the eager-loading is scoped to
     * that path rather than forced globally via the model's `$with` (which would
     * break minimal loads and the EmailTemplate/PushTemplate subclasses).
     */
    public function test_serializing_article_list_is_bounded_and_does_not_grow_with_count(): void
    {
        /** @var ArticleRepositoryContract $repository */
        $repository = $this->app->make(ArticleRepositoryContract::class);

        $makeArticle = function (): void {
            /** @var Article $article */
            $article = Article::factory()->create();

            $iteration = ArticleIteration::factory()->create([
                'article_id' => $article->id,
                'content' => 'content',
            ]);

            ArticleVersion::factory()->create([
                'article_id' => $article->id,
                'article_iteration_id' => $iteration->id,
            ]);
        };

        $countQueriesForListingOf = function (int $articleCount) use ($makeArticle, $repository): int {
            Article::query()->forceDelete();

            for ($i = 0; $i < $articleCount; $i++) {
                $makeArticle();
            }

            DB::flushQueryLog();
            DB::enableQueryLog();

            // Exercise the real index/listing path. Passing limit=0 returns a
            // plain collection. Serializing exercises the `content` +
            // `last_iteration_content` appends on every row.
            $repository->findAll(limit: 0)->toArray();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $queriesForOne = $countQueriesForListingOf(1);
        $queriesForMany = $countQueriesForListingOf(5);

        // The whole point: the per-row N+1 is gone, so listing 5 articles costs
        // the same bounded number of queries as listing 1 (NOT 5x).
        $this->assertSame(
            $queriesForOne,
            $queriesForMany,
            'The article index must not issue more queries as the row count grows (N+1 regression).'
        );

        // Guard the absolute ceiling too: base select + the eager-loaded
        // relations (latestVersion, its articleIteration, latestIteration).
        $this->assertLessThanOrEqual(
            5,
            $queriesForMany,
            'The article index should take a small, bounded number of queries.'
        );
    }

    /**
     * Asserts the N+1 fix is actually wired on the INDEX path: the index
     * repository eager-loads the latest version/iteration relations, they are
     * hidden from the serialized shape, and their data is surfaced through the
     * appends. A bare model load (not the index path) does NOT eager-load them
     * (the relations are intentionally not on the model's `$with`).
     */
    public function test_latest_relations_are_eager_loaded_on_index_hidden_and_feed_the_appends(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        $iteration = ArticleIteration::factory()->create([
            'article_id' => $article->id,
            'content' => 'Hello',
        ]);

        ArticleVersion::factory()->create([
            'article_id' => $article->id,
            'article_iteration_id' => $iteration->id,
        ]);

        // A bare model load must NOT eager-load the helper relations: they are
        // scoped to the index path, not forced globally via `$with`.
        /** @var Article $bare */
        $bare = Article::query()->findOrFail($article->id);
        $this->assertFalse($bare->relationLoaded('latestVersion'));
        $this->assertFalse($bare->relationLoaded('latestIteration'));

        // The index path eager-loads them.
        /** @var ArticleRepositoryContract $repository */
        $repository = $this->app->make(ArticleRepositoryContract::class);
        /** @var Article $fresh */
        $fresh = $repository->findAll(limit: 0)->firstOrFail();

        $this->assertTrue($fresh->relationLoaded('latestVersion'));
        $this->assertTrue($fresh->relationLoaded('latestIteration'));

        // Hidden from serialization so the JSON shape is unchanged...
        $array = $fresh->toArray();
        $this->assertArrayNotHasKey('latest_version', $array);
        $this->assertArrayNotHasKey('latest_iteration', $array);

        // ...but their values feed the appended attributes.
        $this->assertSame('Hello', $array['content']);
        $this->assertSame('Hello', $array['last_iteration_content']);
    }
}
