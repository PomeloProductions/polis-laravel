<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Collection;
use Polis\Contracts\Models\CanBeStatisticTargetContract;

/**
 * Interface StatisticSynchronizationServiceContract
 */
interface StatisticSynchronizationServiceContract
{
    /**
     * Takes in a model that can be a statistic target, and ensures that all necessary target
     * statistics exist for that model based on the available statistics for its type
     *
     * @return Collection|TargetStatistic[]
     */
    public function synchronizeTargetStatistics(CanBeStatisticTargetContract $model): Collection;

    /**
     * Create target statistics for a newly created statistic.
     *
     * @return Collection|TargetStatistic[]
     */
    public function createTargetStatisticsForStatistic(Statistic $statistic): Collection;
}
