<?php

declare(strict_types=1);

namespace App\Models\Wiki;

use App\Models\Statistic\Statistic;
use App\Models\User\ArticleNote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polis\Contracts\Models\CanAggregateViaQueryContract;
use Polis\Services\Statistic\StatisticFilterToQuery;

/**
 * Class AggregatableArticle
 *
 * Dummy consumer-app target that opts in to the SQL push-down aggregation
 * seam ({@see CanAggregateViaQueryContract}). It shares the Article table and
 * the `articleNotes` relation, but instead of letting the processing service
 * load every note into memory it pushes filtering + counting down to SQL via
 * {@see StatisticFilterToQuery}.
 *
 * This mirrors how a real large-aggregate target (e.g. a HighScoresCenter
 * "Year" counting hundreds of thousands of score rows) would implement the
 * contract on the statistics scaffolding. The integration test asserts the
 * result is byte-for-byte identical to the in-memory path for the same data.
 */
class AggregatableArticle extends Article implements CanAggregateViaQueryContract
{
    /**
     * Share the base Article table/data so parity can be asserted against the
     * in-memory Article path over the exact same rows.
     */
    protected $table = 'articles';

    /**
     * Pin the foreign key to article_id. Eloquent would otherwise infer
     * aggregatable_article_id from this subclass's name; sharing the articles
     * table means the notes still point at article_id.
     */
    public function articleNotes(): HasMany
    {
        return $this->hasMany(ArticleNote::class, 'article_id');
    }

    /**
     * {@inheritDoc}
     */
    public function aggregateForStatistic(Statistic $statistic, Model $target): array
    {
        $translator = new StatisticFilterToQuery;

        $filters = $statistic->filters;

        $uniqueFilter = $filters->first(
            static fn ($filter) => $filter->operator === 'unique'
        );

        // Build the query over the relation named by the statistic, scoped to
        // this target, then let the translator push the filters into SQL.
        $query = $target->{$statistic->relation}()->getQuery();
        $translator->applyFilters($query, $filters);

        return $translator->aggregate($query, $uniqueFilter);
    }
}
