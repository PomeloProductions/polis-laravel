<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Contracts\Repositories\User\TodoTaskNodeRepositoryContract;
use App\Models\User\TodoTaskNode;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class TodoTaskNodeRepository extends BaseRepositoryAbstract implements TodoTaskNodeRepositoryContract
{
    public function __construct(TodoTaskNode $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
