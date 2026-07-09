<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\TodoSubItemRepositoryContract;
use Polis\Models\User\TodoSubItem;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TodoSubItemRepository
 */
class TodoSubItemRepository extends BaseRepositoryAbstract implements TodoSubItemRepositoryContract
{
    public function __construct(TodoSubItem $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
