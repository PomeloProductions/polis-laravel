<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Contracts\Repositories\User\TodoBalanceRepositoryContract;
use App\Models\User\TodoBalance;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class TodoBalanceRepository extends BaseRepositoryAbstract implements TodoBalanceRepositoryContract
{
    public function __construct(TodoBalance $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
