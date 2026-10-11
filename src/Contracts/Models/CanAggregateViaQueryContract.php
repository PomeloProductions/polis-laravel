<?php

declare(strict_types=1);

namespace Polis\Contracts\Models;

use App\Models\Statistic\Statistic;
use Illuminate\Database\Eloquent\Model;
use Polis\Services\Relations\RelationTraversalService;
use Polis\Services\Statistic\TargetStatisticProcessingService;

/**
 * Interface CanAggregateViaQueryContract
 *
 * Opt-in seam for statistic targets that can compute a statistic's aggregate
 * by pushing filtering + counting down to SQL instead of loading the whole
 * relation into a PHP collection and counting in memory.
 *
 * A target (or dedicated aggregation root) that implements this contract is
 * used by {@see TargetStatisticProcessingService}
 * in place of the default in-memory
 * {@see RelationTraversalService} path. It exists so
 * targets spanning very large relations (e.g. a HighScoresCenter "Year"
 * aggregating hundreds of thousands of rows) can be computed without
 * exhausting memory.
 *
 * Implementations MUST return the SAME result shape the in-memory path
 * produces so downstream `target_statistics.result` consumers are unaffected:
 *   - a plain count:        ['total' => int]
 *   - a `unique`/grouped:   [<value> => int, ...]  (one entry per distinct
 *                           value of the unique field, mirroring
 *                           {@see TargetStatisticProcessingService}'s
 *                           processUniqueResults()).
 */
interface CanAggregateViaQueryContract
{
    /**
     * Compute the statistic's aggregate for the given target entirely in SQL.
     *
     * @param  Statistic  $statistic  The statistic whose filters/relation drive the aggregate.
     * @param  Model  $target  The target the statistic is being computed for.
     * @return array<array-key, int> The aggregate result, matching the in-memory path's shape.
     */
    public function aggregateForStatistic(Statistic $statistic, Model $target): array;
}
