<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\TodoVacationPeriodRepositoryContract;
use Polis\Models\User\TodoVacationPeriod;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TodoVacationPeriodRepository
 */
class TodoVacationPeriodRepository extends BaseRepositoryAbstract implements TodoVacationPeriodRepositoryContract
{
    public function __construct(TodoVacationPeriod $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
