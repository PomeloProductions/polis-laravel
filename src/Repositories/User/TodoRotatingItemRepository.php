<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\TodoRotatingItemRepositoryContract;
use Polis\Models\User\TodoRotatingItem;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TodoRotatingItemRepository
 */
class TodoRotatingItemRepository extends BaseRepositoryAbstract implements TodoRotatingItemRepositoryContract
{
    public function __construct(TodoRotatingItem $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
