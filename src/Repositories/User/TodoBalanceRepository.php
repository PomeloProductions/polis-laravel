<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\TodoBalanceRepositoryContract;
use Polis\Models\User\TodoBalance;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TodoBalanceRepository
 */
class TodoBalanceRepository extends BaseRepositoryAbstract implements TodoBalanceRepositoryContract
{
    public function __construct(TodoBalance $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
