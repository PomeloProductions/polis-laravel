<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract;
use Polis\Models\User\UserPageComponent;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class UserPageComponentRepository extends BaseRepositoryAbstract implements UserPageComponentRepositoryContract
{
    public function __construct(UserPageComponent $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
