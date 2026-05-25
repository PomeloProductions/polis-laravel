<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Statistic;

use App\Models\Statistic\TargetStatistic;

/**
 * Interface TargetStatisticProcessingServiceContract
 */
interface TargetStatisticProcessingServiceContract
{
    /**
     * Processes a single target statistic and updates its result
     */
    public function processSingleTargetStatistic(TargetStatistic $targetStatistic): void;
}
