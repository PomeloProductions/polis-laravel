<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\TodoTaskNodeRepositoryContract;
use Polis\Models\User\TodoTaskNode;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TodoTaskNodeRepository
 */
class TodoTaskNodeRepository extends BaseRepositoryAbstract implements TodoTaskNodeRepositoryContract
{
    public function __construct(TodoTaskNode $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
