<?php

declare(strict_types=1);

namespace Polis\Repositories\Statistic;

use App\Models\Statistic\StatisticFilter;
use Polis\Contracts\Repositories\Statistic\StatisticFilterRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class StatisticFilterRepository extends BaseRepositoryAbstract implements StatisticFilterRepositoryContract
{
    public function __construct(StatisticFilter $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
