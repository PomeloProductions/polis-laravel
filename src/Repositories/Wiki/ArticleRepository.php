<?php

declare(strict_types=1);

namespace Polis\Repositories\Wiki;

use App\Models\Statistic\Statistic;
use App\Models\User\User;
use App\Models\Wiki\Article;
use Polis\Contracts\Repositories\Statistic\StatisticRepositoryContract;
use Polis\Contracts\Repositories\Wiki\ArticleRepositoryContract;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Repositories\Traits\NotImplemented\Delete;
use Polis\Traits\CanGetAndUnset;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class ArticleRepository
 */
class ArticleRepository extends BaseRepositoryAbstract implements ArticleRepositoryContract
{
    use CanGetAndUnset;
    use Delete;

    /**
     * ArticleRepository constructor.
     */
    public function __construct(
        Article $model,
        LogContract $log,
        private readonly StatisticRepositoryContract $statisticRepository
    ) {
        parent::__construct($model, $log);
    }

    /**
     * Override create to handle categories sync
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = []): BaseModelAbstract
    {
        $categories = $this->getAndUnset($data, 'categories');

        /** @var Article $article */
        $article = parent::create($data, $relatedModel, $forcedValues);

        if ($categories !== null) {
            $syncData = collect($categories)->mapWithKeys(fn ($cat) => [
                $cat['category_id'] => array_filter(['relevance' => $cat['relevance'] ?? null]),
            ])->toArray();
            $article->categories()->sync($syncData);
        }

        return $article;
    }

    /**
     * Override update to handle categories sync
     */
    public function update(BaseModelAbstract $model, array $data, array $forcedValues = []): BaseModelAbstract
    {
        $categories = $this->getAndUnset($data, 'categories');

        /** @var Article $article */
        $article = parent::update($model, $data, $forcedValues);

        if ($categories !== null) {
            $syncData = collect($categories)->mapWithKeys(fn ($cat) => [
                $cat['category_id'] => array_filter(['relevance' => $cat['relevance'] ?? null]),
            ])->toArray();
            $article->categories()->sync($syncData);
        }

        return $article;
    }

    /**
     * Selects an article for a user based on their note completion status and article statistics.
     *
     * Priority order:
     * 1. Articles where user has NO note started (never returns articles with completed notes)
     * 2. Articles where user has an incomplete note
     *
     * Within each priority group, orders by:
     * - Lowest total_completed_notes statistic (fewest completions by all users)
     * - Lowest total_notes statistic (fewest notes started by all users)
     * - Random order for variety when statistics are equal
     */
    public function selectArticleForUser(User $user): ?Article
    {
        // Get all statistics for the article model
        $statistics = $this->statisticRepository->findAllForModel('article');

        // Build a map of statistic names to their IDs
        $statisticMap = [];
        foreach ($statistics as $statistic) {
            $statisticMap[$statistic->name] = $statistic->id;
        }

        // Start building the query
        $query = $this->model->newQuery()
            ->select('articles.*')
            ->leftJoin('article_notes as user_notes', function ($join) use ($user) {
                $join->on('articles.id', '=', 'user_notes.article_id')
                    ->where('user_notes.user_id', '=', $user->id)
                    ->whereNull('user_notes.deleted_at');
            });

        // Conditionally add join for total_completed_notes statistic
        if (isset($statisticMap['total_completed_notes'])) {
            $completedStatId = $statisticMap['total_completed_notes'];
            $query->leftJoin('target_statistics as completed_stats', function ($join) use ($completedStatId) {
                $join->on('articles.id', '=', 'completed_stats.target_id')
                    ->where('completed_stats.target_type', '=', 'article')
                    ->where('completed_stats.statistic_id', '=', $completedStatId);
            });
        }

        // Conditionally add join for total_notes statistic
        if (isset($statisticMap['total_notes'])) {
            $totalNotesStatId = $statisticMap['total_notes'];
            $query->leftJoin('target_statistics as total_stats', function ($join) use ($totalNotesStatId) {
                $join->on('articles.id', '=', 'total_stats.target_id')
                    ->where('total_stats.target_type', '=', 'article')
                    ->where('total_stats.statistic_id', '=', $totalNotesStatId);
            });
        }

        // Exclude articles where user has completed a note
        $query->where(function ($query) {
            $query->whereNull('user_notes.id')
                ->orWhereNull('user_notes.completed_at');
        });

        // Order by priority: no note (1) before incomplete note (2)
        $query->orderByRaw('CASE WHEN user_notes.id IS NULL THEN 1 ELSE 2 END ASC');

        // Conditionally add ordering by completed notes count
        if (isset($statisticMap['total_completed_notes'])) {
            $query->orderByRaw('COALESCE(CAST(JSON_EXTRACT(completed_stats.result, "$.total") AS UNSIGNED), 0) ASC');
        }

        // Conditionally add ordering by total notes count
        if (isset($statisticMap['total_notes'])) {
            $query->orderByRaw('COALESCE(CAST(JSON_EXTRACT(total_stats.result, "$.total") AS UNSIGNED), 0) ASC');
        }

        // Finally order randomly for variety
        $query->inRandomOrder();

        return $query->first();
    }
}
