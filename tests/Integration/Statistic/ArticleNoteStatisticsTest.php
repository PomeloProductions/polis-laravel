<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use App\Models\User\ArticleNote;
use App\Models\User\User;
use App\Models\Wiki\Article;
use Database\Seeders\ArticleNoteStatisticsSeeder;
use Polis\Contracts\Services\Statistic\StatisticSynchronizationServiceContract;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class ArticleNoteStatisticsTest
 */
final class ArticleNoteStatisticsTest extends TestCase
{
    use DatabaseSetupTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        // Seed article note statistics
        $this->seed(ArticleNoteStatisticsSeeder::class);
    }

    public function test_total_notes_statistic_updates_on_create(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        // Get the total_notes statistic
        $statistic = Statistic::where('name', 'total_notes')->first();
        $this->assertNotNull($statistic);

        // Synchronize to ensure TargetStatistics exist for this article
        $synchronizationService = app(StatisticSynchronizationServiceContract::class);
        $synchronizationService->synchronizeTargetStatistics($article);

        // Create an ArticleNote
        ArticleNote::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        // Refresh the article and check for target statistics
        $article->refresh();
        $targetStatistic = TargetStatistic::where('target_id', $article->id)
            ->where('target_type', 'article')
            ->where('statistic_id', $statistic->id)
            ->first();

        $this->assertNotNull($targetStatistic);
        $this->assertEquals(1, $targetStatistic->result['total']);
    }

    public function test_total_completed_notes_statistic_updates_on_create(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        // Get the total_completed_notes statistic
        $statistic = Statistic::where('name', 'total_completed_notes')->first();
        $this->assertNotNull($statistic);

        // Synchronize to ensure TargetStatistics exist for this article
        $synchronizationService = app(StatisticSynchronizationServiceContract::class);
        $synchronizationService->synchronizeTargetStatistics($article);

        // Create a completed ArticleNote
        ArticleNote::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'completed_at' => now(),
        ]);

        // Refresh the article and check for target statistics
        $article->refresh();
        $targetStatistic = TargetStatistic::where('target_id', $article->id)
            ->where('target_type', 'article')
            ->where('statistic_id', $statistic->id)
            ->first();

        $this->assertNotNull($targetStatistic);
        $this->assertEquals(1, $targetStatistic->result['total']);
    }

    public function test_total_notes_statistic_updates_on_delete(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        /** @var User $user1 */
        $user1 = User::factory()->create();

        /** @var User $user2 */
        $user2 = User::factory()->create();

        // Get the total_notes statistic
        $statistic = Statistic::where('name', 'total_notes')->first();
        $this->assertNotNull($statistic);

        // Synchronize to ensure TargetStatistics exist for this article
        $synchronizationService = app(StatisticSynchronizationServiceContract::class);
        $synchronizationService->synchronizeTargetStatistics($article);

        // Create two ArticleNotes with different users
        $note1 = ArticleNote::factory()->create([
            'user_id' => $user1->id,
            'article_id' => $article->id,
        ]);

        ArticleNote::factory()->create([
            'user_id' => $user2->id,
            'article_id' => $article->id,
        ]);

        // Verify we have 2 notes
        $targetStatistic = TargetStatistic::where('target_id', $article->id)
            ->where('target_type', 'article')
            ->where('statistic_id', $statistic->id)
            ->first();

        $this->assertNotNull($targetStatistic);
        $this->assertEquals(2, $targetStatistic->result['total']);

        // Delete one note
        $note1->delete();

        // Verify we now have 1 note
        $targetStatistic->refresh();
        $this->assertEquals(1, $targetStatistic->result['total']);
    }

    public function test_total_completed_notes_statistic_updates_on_update(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        // Get the total_completed_notes statistic
        $statistic = Statistic::where('name', 'total_completed_notes')->first();
        $this->assertNotNull($statistic);

        // Synchronize to ensure TargetStatistics exist for this article
        $synchronizationService = app(StatisticSynchronizationServiceContract::class);
        $synchronizationService->synchronizeTargetStatistics($article);

        // Create an incomplete ArticleNote
        $note = ArticleNote::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'completed_at' => null,
        ]);

        // Verify we have 0 completed notes
        $targetStatistic = TargetStatistic::where('target_id', $article->id)
            ->where('target_type', 'article')
            ->where('statistic_id', $statistic->id)
            ->first();

        $this->assertNotNull($targetStatistic);
        $this->assertEquals(0, $targetStatistic->result['total']);

        // Mark note as completed
        $note->update(['completed_at' => now()]);

        // Verify we now have 1 completed note
        $targetStatistic->refresh();
        $this->assertEquals(1, $targetStatistic->result['total']);
    }

    public function test_multiple_notes_statistics(): void
    {
        /** @var Article $article */
        $article = Article::factory()->create();

        /** @var User $user1 */
        $user1 = User::factory()->create();

        /** @var User $user2 */
        $user2 = User::factory()->create();

        /** @var User $user3 */
        $user3 = User::factory()->create();

        // Get both statistics
        $totalNotesStatistic = Statistic::where('name', 'total_notes')->first();
        $totalCompletedNotesStatistic = Statistic::where('name', 'total_completed_notes')->first();

        // Synchronize to ensure TargetStatistics exist for this article
        $synchronizationService = app(StatisticSynchronizationServiceContract::class);
        $synchronizationService->synchronizeTargetStatistics($article);

        // Create 3 notes: 2 completed, 1 incomplete (each with different user)
        ArticleNote::factory()->create([
            'user_id' => $user1->id,
            'article_id' => $article->id,
            'completed_at' => now(),
        ]);

        ArticleNote::factory()->create([
            'user_id' => $user2->id,
            'article_id' => $article->id,
            'completed_at' => now(),
        ]);

        ArticleNote::factory()->create([
            'user_id' => $user3->id,
            'article_id' => $article->id,
            'completed_at' => null,
        ]);

        // Verify total notes = 3
        $totalNotesTargetStat = TargetStatistic::where('target_id', $article->id)
            ->where('target_type', 'article')
            ->where('statistic_id', $totalNotesStatistic->id)
            ->first();

        $this->assertNotNull($totalNotesTargetStat);
        $this->assertEquals(3, $totalNotesTargetStat->result['total']);

        // Verify completed notes = 2
        $totalCompletedNotesTargetStat = TargetStatistic::where('target_id', $article->id)
            ->where('target_type', 'article')
            ->where('statistic_id', $totalCompletedNotesStatistic->id)
            ->first();

        $this->assertNotNull($totalCompletedNotesTargetStat);
        $this->assertEquals(2, $totalCompletedNotesTargetStat->result['total']);
    }
}
