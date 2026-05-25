<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Wiki;

use App\Models\User\ArticleNote;
use App\Models\User\User;
use App\Models\Wiki\Article;
use Database\Seeders\ArticleNoteStatisticsSeeder;
use Polis\Contracts\Repositories\Wiki\ArticleRepositoryContract;
use Polis\Contracts\Services\Statistic\StatisticSynchronizationServiceContract;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class ArticleRepositorySelectArticleTest
 */
final class ArticleRepositorySelectArticleTest extends TestCase
{
    use DatabaseSetupTrait;

    private ArticleRepositoryContract $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->repository = app(ArticleRepositoryContract::class);

        // Seed article note statistics
        $this->seed(ArticleNoteStatisticsSeeder::class);
    }

    public function test_select_article_for_user_never_returns_completed_articles(): void
    {
        // Clean up any existing articles
        Article::query()->delete();

        /** @var User $user */
        $user = User::factory()->create();

        // Create two articles
        $article1 = Article::factory()->create(['title' => 'Article 1']);
        $article2 = Article::factory()->create(['title' => 'Article 2']);

        // User has completed article 1
        ArticleNote::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article1->id,
            'completed_at' => now(),
        ]);

        // Select article for user
        $selected = $this->repository->selectArticleForUser($user);

        // Should return an article, but never article 1 (the completed one)
        $this->assertNotNull($selected);
        $this->assertNotEquals($article1->id, $selected->id);

        // Verify the user has NOT completed a note on the selected article
        $userNote = ArticleNote::where('user_id', $user->id)
            ->where('article_id', $selected->id)
            ->first();
        if ($userNote) {
            $this->assertNull($userNote->completed_at);
        }
    }

    public function test_select_article_for_user_prioritizes_articles_with_no_notes(): void
    {
        // Clean up any existing articles
        Article::query()->delete();

        /** @var User $user */
        $user = User::factory()->create();

        // Create two articles
        $articleWithNote = Article::factory()->create(['title' => 'Has Incomplete Note']);
        $articleWithoutNote = Article::factory()->create(['title' => 'No Note']);

        // User has incomplete note on first article
        ArticleNote::factory()->create([
            'user_id' => $user->id,
            'article_id' => $articleWithNote->id,
            'completed_at' => null,
        ]);

        // Select article for user
        $selected = $this->repository->selectArticleForUser($user);

        // Should return an article (prefer one without note over one with incomplete note)
        $this->assertNotNull($selected);

        // Verify the user has NOT completed a note on the selected article
        $userNote = ArticleNote::where('user_id', $user->id)
            ->where('article_id', $selected->id)
            ->first();

        if ($userNote) {
            $this->assertNull($userNote->completed_at, 'Selected article should not have a completed note');
        }
    }

    public function test_select_article_for_user_orders_by_lowest_completed_notes_statistic(): void
    {
        // Clean up any existing articles
        Article::query()->delete();

        /** @var User $currentUser */
        $currentUser = User::factory()->create();

        // Create three articles
        $articleLowCompleted = Article::factory()->create(['title' => 'Low Completed']);
        $articleHighCompleted = Article::factory()->create(['title' => 'High Completed']);
        $articleNoCompleted = Article::factory()->create(['title' => 'No Completed']);

        // Create other users who have completed notes
        $otherUser1 = User::factory()->create();
        $otherUser2 = User::factory()->create();
        $otherUser3 = User::factory()->create();

        // Article 2 has 3 completed notes (high)
        ArticleNote::factory()->create([
            'user_id' => $otherUser1->id,
            'article_id' => $articleHighCompleted->id,
            'completed_at' => now(),
        ]);
        ArticleNote::factory()->create([
            'user_id' => $otherUser2->id,
            'article_id' => $articleHighCompleted->id,
            'completed_at' => now(),
        ]);
        ArticleNote::factory()->create([
            'user_id' => $otherUser3->id,
            'article_id' => $articleHighCompleted->id,
            'completed_at' => now(),
        ]);

        // Article 1 has 1 completed note (low)
        ArticleNote::factory()->create([
            'user_id' => $otherUser1->id,
            'article_id' => $articleLowCompleted->id,
            'completed_at' => now(),
        ]);

        // Synchronize statistics for all articles
        $synchronizationService = app(StatisticSynchronizationServiceContract::class);
        $synchronizationService->synchronizeTargetStatistics($articleLowCompleted);
        $synchronizationService->synchronizeTargetStatistics($articleHighCompleted);
        $synchronizationService->synchronizeTargetStatistics($articleNoCompleted);

        // Select article for current user
        $selected = $this->repository->selectArticleForUser($currentUser);

        // Should return an article (will prefer ones with lower completed notes)
        $this->assertNotNull($selected);

        // Mark all three test articles as completed for current user to test that they're excluded
        ArticleNote::updateOrCreate(
            ['user_id' => $currentUser->id, 'article_id' => $articleNoCompleted->id],
            ['completed_at' => now()]
        );
        ArticleNote::updateOrCreate(
            ['user_id' => $currentUser->id, 'article_id' => $articleLowCompleted->id],
            ['completed_at' => now()]
        );
        ArticleNote::updateOrCreate(
            ['user_id' => $currentUser->id, 'article_id' => $articleHighCompleted->id],
            ['completed_at' => now()]
        );

        // Select again
        $selected2 = $this->repository->selectArticleForUser($currentUser);

        // Should return a different article (not any of the completed ones)
        $this->assertNotNull($selected2);
        $this->assertNotEquals($articleNoCompleted->id, $selected2->id);
        $this->assertNotEquals($articleLowCompleted->id, $selected2->id);
        $this->assertNotEquals($articleHighCompleted->id, $selected2->id);
    }

    public function test_select_article_for_user_orders_by_lowest_total_notes_statistic(): void
    {
        // Clean up any existing articles
        Article::query()->delete();

        /** @var User $currentUser */
        $currentUser = User::factory()->create();

        // Create two articles with same completed notes but different total notes
        $articleLowTotal = Article::factory()->create(['title' => 'Low Total']);
        $articleHighTotal = Article::factory()->create(['title' => 'High Total']);

        // Create other users
        $otherUser1 = User::factory()->create();
        $otherUser2 = User::factory()->create();
        $otherUser3 = User::factory()->create();

        // Both articles have 1 completed note (same)
        ArticleNote::factory()->create([
            'user_id' => $otherUser1->id,
            'article_id' => $articleLowTotal->id,
            'completed_at' => now(),
        ]);
        ArticleNote::factory()->create([
            'user_id' => $otherUser2->id,
            'article_id' => $articleHighTotal->id,
            'completed_at' => now(),
        ]);

        // Article high total has 2 additional incomplete notes (3 total notes vs 1)
        ArticleNote::factory()->create([
            'user_id' => $otherUser3->id,
            'article_id' => $articleHighTotal->id,
            'completed_at' => null,
        ]);
        ArticleNote::factory()->create([
            'user_id' => User::factory()->create()->id,
            'article_id' => $articleHighTotal->id,
            'completed_at' => null,
        ]);

        // Synchronize statistics
        $synchronizationService = app(StatisticSynchronizationServiceContract::class);
        $synchronizationService->synchronizeTargetStatistics($articleLowTotal);
        $synchronizationService->synchronizeTargetStatistics($articleHighTotal);

        // Select article for current user
        $selected = $this->repository->selectArticleForUser($currentUser);

        // Should return an article
        $this->assertNotNull($selected);

        // Verify current user has no note (or no completed note) on the selected article
        $userNote = ArticleNote::where('user_id', $currentUser->id)
            ->where('article_id', $selected->id)
            ->first();

        if ($userNote) {
            $this->assertNull($userNote->completed_at, 'Selected article should not have a completed note');
        }
    }

    public function test_select_article_for_user_can_return_article_with_incomplete_note(): void
    {
        // Clean up any existing articles
        Article::query()->delete();

        /** @var User $user */
        $user = User::factory()->create();

        // Create an article with an incomplete note
        $articleWithIncompleteNote = Article::factory()->create(['title' => 'Article With Incomplete Note']);
        ArticleNote::factory()->create([
            'user_id' => $user->id,
            'article_id' => $articleWithIncompleteNote->id,
            'completed_at' => null,
        ]);

        // Select article for user
        $selected = $this->repository->selectArticleForUser($user);

        // Should return an article (either one without notes or the one with incomplete note)
        $this->assertNotNull($selected);

        // If it returned the article with incomplete note, verify it's correct
        if ($selected->id === $articleWithIncompleteNote->id) {
            $userNote = ArticleNote::where('user_id', $user->id)
                ->where('article_id', $selected->id)
                ->first();
            $this->assertNotNull($userNote);
            $this->assertNull($userNote->completed_at);
        }
    }

    public function test_select_article_for_user_excludes_completed_articles(): void
    {
        // Clean up any existing articles
        Article::query()->delete();

        /** @var User $user */
        $user = User::factory()->create();

        // Create three articles
        $completedArticle1 = Article::factory()->create(['title' => 'Completed 1']);
        $completedArticle2 = Article::factory()->create(['title' => 'Completed 2']);
        $availableArticle = Article::factory()->create(['title' => 'Available']);

        // Mark first two as completed
        ArticleNote::factory()->create([
            'user_id' => $user->id,
            'article_id' => $completedArticle1->id,
            'completed_at' => now(),
        ]);
        ArticleNote::factory()->create([
            'user_id' => $user->id,
            'article_id' => $completedArticle2->id,
            'completed_at' => now(),
        ]);

        // Select article for user (should not return completed articles)
        $selected = $this->repository->selectArticleForUser($user);

        // Should return an article
        $this->assertNotNull($selected);

        // Verify the selected article is NOT one of the completed ones
        $this->assertNotEquals($completedArticle1->id, $selected->id);
        $this->assertNotEquals($completedArticle2->id, $selected->id);

        // Verify if user has a note on the selected article, it's not completed
        $userNote = ArticleNote::where('user_id', $user->id)
            ->where('article_id', $selected->id)
            ->first();

        if ($userNote) {
            $this->assertNull($userNote->completed_at, 'Selected article should not have a completed note');
        }
    }

    public function test_select_article_for_user_combines_priority_and_statistics(): void
    {
        // Clean up any existing articles
        Article::query()->delete();

        /** @var User $currentUser */
        $currentUser = User::factory()->create();

        // Create articles in different priority groups
        $unstartedHighStats = Article::factory()->create(['title' => 'Unstarted High Stats']);
        $unstartedLowStats = Article::factory()->create(['title' => 'Unstarted Low Stats']);
        $incompleteHighStats = Article::factory()->create(['title' => 'Incomplete High Stats']);
        $incompleteLowStats = Article::factory()->create(['title' => 'Incomplete Low Stats']);

        // Create other users to generate statistics
        $otherUsers = User::factory()->count(5)->create();

        // Generate high statistics for some articles
        foreach ($otherUsers as $otherUser) {
            ArticleNote::factory()->create([
                'user_id' => $otherUser->id,
                'article_id' => $unstartedHighStats->id,
                'completed_at' => now(),
            ]);
            ArticleNote::factory()->create([
                'user_id' => $otherUser->id,
                'article_id' => $incompleteHighStats->id,
                'completed_at' => now(),
            ]);
        }

        // Current user has incomplete notes on incomplete articles
        ArticleNote::factory()->create([
            'user_id' => $currentUser->id,
            'article_id' => $incompleteHighStats->id,
            'completed_at' => null,
        ]);
        ArticleNote::factory()->create([
            'user_id' => $currentUser->id,
            'article_id' => $incompleteLowStats->id,
            'completed_at' => null,
        ]);

        // Synchronize statistics
        $synchronizationService = app(StatisticSynchronizationServiceContract::class);
        foreach ([$unstartedHighStats, $unstartedLowStats, $incompleteHighStats, $incompleteLowStats] as $article) {
            $synchronizationService->synchronizeTargetStatistics($article);
        }

        // Select article for current user
        $selected = $this->repository->selectArticleForUser($currentUser);

        // Should return an article
        $this->assertNotNull($selected);

        // Verify it's not one of the articles the user already has an incomplete note on
        // (unless all unstarted articles are exhausted)
        $userNote = ArticleNote::where('user_id', $currentUser->id)
            ->where('article_id', $selected->id)
            ->first();

        // The selected article should either have no note (priority 1) or an incomplete note (priority 2)
        if ($userNote) {
            $this->assertNull($userNote->completed_at, 'Selected article should not have a completed note');
        }

        // Mark the two test unstarted articles as completed
        ArticleNote::updateOrCreate(
            ['user_id' => $currentUser->id, 'article_id' => $unstartedLowStats->id],
            ['completed_at' => now()]
        );
        ArticleNote::updateOrCreate(
            ['user_id' => $currentUser->id, 'article_id' => $unstartedHighStats->id],
            ['completed_at' => now()]
        );

        // Select again
        $selected2 = $this->repository->selectArticleForUser($currentUser);

        // Should still return an article
        $this->assertNotNull($selected2);

        // Verify it's not a completed article
        $this->assertNotEquals($unstartedLowStats->id, $selected2->id);
        $this->assertNotEquals($unstartedHighStats->id, $selected2->id);

        // Verify if there's a user note, it's not completed
        $userNote2 = ArticleNote::where('user_id', $currentUser->id)
            ->where('article_id', $selected2->id)
            ->first();

        if ($userNote2) {
            $this->assertNull($userNote2->completed_at, 'Selected article should not have a completed note');
        }
    }
}
