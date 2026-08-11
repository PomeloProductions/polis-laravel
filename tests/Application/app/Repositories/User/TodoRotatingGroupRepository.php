<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Contracts\Repositories\User\TodoRotatingGroupRepositoryContract;
use App\Models\User\TodoRotatingGroup;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class TodoRotatingGroupRepository extends BaseRepositoryAbstract implements TodoRotatingGroupRepositoryContract
{
    public function __construct(TodoRotatingGroup $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
