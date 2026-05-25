<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\Statistic;

use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Polis\Contracts\Models\CanBeStatisticTargetContract;
use Polis\Contracts\Repositories\BaseRepositoryContract;

/**
 * Interface TargetStatisticRepositoryContract
 */
interface TargetStatisticRepositoryContract extends BaseRepositoryContract
{
    /**
     * Creates a new target statistic model
     */
    public function createForTarget(CanBeStatisticTargetContract $target, array $data): TargetStatistic;

    /**
     * Find all statistics for a specific target
     */
    public function findAllForTarget(CanBeStatisticTargetContract $target): Collection;

    /**
     * Find a specific statistic for a target
     */
    public function findForTarget(CanBeStatisticTargetContract $target, int $statisticId): ?TargetStatistic;
}
