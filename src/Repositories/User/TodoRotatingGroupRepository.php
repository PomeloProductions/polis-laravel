<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\TodoRotatingGroupRepositoryContract;
use Polis\Models\User\TodoRotatingGroup;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TodoRotatingGroupRepository
 */
class TodoRotatingGroupRepository extends BaseRepositoryAbstract implements TodoRotatingGroupRepositoryContract
{
    public function __construct(TodoRotatingGroup $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
