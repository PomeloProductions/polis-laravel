<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\Statistic;

use Illuminate\Support\Collection;
use Polis\Contracts\Models\IsAnEntityContract;
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

    /**
     * Get the global (NULL-owner) statistics PLUS those owned by the given
     * entity. When no owner is supplied, only the global statistics are
     * returned. This is the "shared + mine" read for per-owner statistics.
     */
    public function findAllGlobalOrOwnedBy(?IsAnEntityContract $owner = null): Collection;
}
