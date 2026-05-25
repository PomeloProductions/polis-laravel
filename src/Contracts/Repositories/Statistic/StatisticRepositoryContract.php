<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\Statistic;

use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\BaseRepositoryContract;

/**
 * Interface StatisticRepositoryContract
 */
interface StatisticRepositoryContract extends BaseRepositoryContract
{
    /**
     * Get all statistics for a given model
     */
    public function findAllForModel(string $model): Collection;
}
