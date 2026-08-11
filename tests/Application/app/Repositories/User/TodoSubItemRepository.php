<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Contracts\Repositories\User\TodoSubItemRepositoryContract;
use App\Models\User\TodoSubItem;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class TodoSubItemRepository extends BaseRepositoryAbstract implements TodoSubItemRepositoryContract
{
    public function __construct(TodoSubItem $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
