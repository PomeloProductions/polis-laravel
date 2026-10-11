<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\Statistic\StatisticFilter;
use App\Models\Statistic\TargetStatistic;
use App\Models\User\ArticleNote;
use App\Models\User\User;
use App\Models\Wiki\AggregatableArticle;
use App\Models\Wiki\Article;
use Polis\Contracts\Models\CanAggregateViaQueryContract;
use Polis\Contracts\Services\Statistic\TargetStatisticProcessingServiceContract;
use Polis\Services\Statistic\TargetStatisticProcessingService;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class SqlPushDownAggregationParityTest
 *
 * Verifies the opt-in SQL push-down aggregation branch in
 * {@see TargetStatisticProcessingService}:
 *
 *  - A target implementing {@see CanAggregateViaQueryContract}
 *    (AggregatableArticle) is aggregated entirely in SQL and produces results
 *    that are IDENTICAL to the legacy in-memory path over the same rows
 *    (parity), for plain counts, filtered counts, and `unique` group-by.
 *  - A target NOT implementing the contract (plain Article) still uses the
 *    unchanged in-memory path.
 */
final class SqlPushDownAggregationParityTest extends ApplicationTestCase
{
    private TargetStatisticProcessingServiceContract $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->service = app(TargetStatisticProcessingServiceContract::class);
    }

    /**
     * Seed an article with a mix of completed/incomplete notes shared by both
     * the in-memory and SQL targets.
     */
    private function seedArticleWithNotes(): Article
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        // 2 completed, 1 incomplete.
        ArticleNote::factory()->create([
            'user_id' => User::factory()->create()->id,
            'article_id' => $article->id,
            'completed_at' => now(),
        ]);
        ArticleNote::factory()->create([
            'user_id' => User::factory()->create()->id,
            'article_id' => $article->id,
            'completed_at' => now(),
        ]);
        ArticleNote::factory()->create([
            'user_id' => User::factory()->create()->id,
            'article_id' => $article->id,
            'completed_at' => null,
        ]);

        return $article;
    }

    /**
     * Build an unsaved TargetStatistic pointing a given target type/id at a
     * statistic, process it, and return the computed result.
     */
    private function processFor(Statistic $statistic, string $targetType, int $targetId): array
    {
        /** @var TargetStatistic $targetStatistic */
        $targetStatistic = TargetStatistic::factory()->create([
            'statistic_id' => $statistic->id,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'result' => null,
        ]);

        $this->service->processSingleTargetStatistic($targetStatistic->fresh());

        return $targetStatistic->fresh()->result;
    }

    public function test_plain_count_matches_between_sql_and_in_memory(): void
    {
        $article = $this->seedArticleWithNotes();

        $statistic = Statistic::factory()->create([
            'name' => 'total_notes_parity',
            'model' => 'article',
            'relation' => 'articleNotes',
        ]);

        $inMemory = $this->processFor($statistic, 'article', $article->id);
        $sql = $this->processFor($statistic, 'aggregatable_article', $article->id);

        $this->assertSame(['total' => 3], $inMemory);
        $this->assertSame($inMemory, $sql, 'SQL push-down result must equal the in-memory result');
    }

    public function test_filtered_count_matches_between_sql_and_in_memory(): void
    {
        $article = $this->seedArticleWithNotes();

        $statistic = Statistic::factory()->create([
            'name' => 'completed_notes_parity',
            'model' => 'article',
            'relation' => 'articleNotes',
        ]);
        StatisticFilter::factory()->create([
            'statistic_id' => $statistic->id,
            'field' => 'completed_at',
            'operator' => '!=',
            'value' => null,
        ]);

        $inMemory = $this->processFor($statistic, 'article', $article->id);
        $sql = $this->processFor($statistic, 'aggregatable_article', $article->id);

        $this->assertSame(['total' => 2], $inMemory);
        $this->assertSame($inMemory, $sql, 'SQL push-down filtered result must equal the in-memory result');
    }

    public function test_unique_group_by_matches_between_sql_and_in_memory(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        // Two distinct responses: "yes" x2, "no" x1.
        foreach (['yes', 'yes', 'no'] as $response) {
            ArticleNote::factory()->create([
                'user_id' => User::factory()->create()->id,
                'article_id' => $article->id,
                'response' => $response,
            ]);
        }

        $statistic = Statistic::factory()->create([
            'name' => 'responses_parity',
            'model' => 'article',
            'relation' => 'articleNotes',
        ]);
        StatisticFilter::factory()->create([
            'statistic_id' => $statistic->id,
            'field' => 'response',
            'operator' => 'unique',
            'value' => null,
        ]);

        $inMemory = $this->processFor($statistic, 'article', $article->id);
        $sql = $this->processFor($statistic, 'aggregatable_article', $article->id);

        $this->assertSame(['yes' => 2, 'no' => 1], $inMemory);

        // Parity on content; GROUP BY row order is not guaranteed, so compare
        // the per-value counts independent of key ordering.
        ksort($inMemory);
        ksort($sql);
        $this->assertSame($inMemory, $sql, 'SQL push-down unique group-by must equal the in-memory result');
    }

    public function test_plain_article_target_is_not_a_query_aggregator(): void
    {
        $article = $this->seedArticleWithNotes();

        $this->assertFalse(
            $article instanceof CanAggregateViaQueryContract,
            'Plain Article must NOT implement the aggregation contract so it keeps the in-memory path'
        );

        $aggregatable = AggregatableArticle::find($article->id);
        $this->assertInstanceOf(
            CanAggregateViaQueryContract::class,
            $aggregatable,
            'AggregatableArticle must opt in to the SQL push-down contract'
        );
    }
}
