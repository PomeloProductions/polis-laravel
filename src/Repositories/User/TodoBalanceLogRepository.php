<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\TodoBalanceLogRepositoryContract;
use Polis\Models\User\TodoBalanceLog;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TodoBalanceLogRepository
 */
class TodoBalanceLogRepository extends BaseRepositoryAbstract implements TodoBalanceLogRepositoryContract
{
    public function __construct(TodoBalanceLog $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
