<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Contracts\Repositories\User\TodoBalanceLogRepositoryContract;
use App\Models\User\TodoBalanceLog;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class TodoBalanceLogRepository extends BaseRepositoryAbstract implements TodoBalanceLogRepositoryContract
{
    public function __construct(TodoBalanceLog $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
