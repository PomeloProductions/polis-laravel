<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\UserPageRepositoryContract;
use Polis\Models\User\UserPage;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class UserPageRepository extends BaseRepositoryAbstract implements UserPageRepositoryContract
{
    public function __construct(UserPage $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
