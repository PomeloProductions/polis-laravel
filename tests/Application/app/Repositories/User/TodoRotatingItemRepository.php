<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Contracts\Repositories\User\TodoRotatingItemRepositoryContract;
use App\Models\User\TodoRotatingItem;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class TodoRotatingItemRepository extends BaseRepositoryAbstract implements TodoRotatingItemRepositoryContract
{
    public function __construct(TodoRotatingItem $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
