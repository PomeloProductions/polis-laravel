<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use Polis\Contracts\Models\CanBeStatisticTargetContract;

/**
 * Interface TargetStatisticProcessingServiceContract
 */
interface TargetStatisticProcessingServiceContract
{
    /**
     * Processes a single target statistic and updates its result
     */
    public function processSingleTargetStatistic(TargetStatistic $targetStatistic): void;

    /**
     * Ensure the (target, statistic) materialized row exists and (re)compute its
     * result SYNCHRONOUSLY, returning the freshly persisted row.
     *
     * This is the single-cell, synchronous counterpart to the queued recount: it
     * finds (or creates) the TargetStatistic for exactly this target + statistic
     * pair, then runs {@see self::processSingleTargetStatistic()} on it — so the
     * value is computed through the SAME path as the background job (the target's
     * query push-down / in-memory traversal) AND written back to
     * `target_statistics.result`. Callers that read a materialized cell on a MISS
     * can use this to compute-and-persist that one cell on the read path, so the
     * next read is a cache hit and the calculation never repeats.
     */
    public function ensureAndRecomputeSingle(
        CanBeStatisticTargetContract $target,
        Statistic $statistic
    ): TargetStatistic;
}
