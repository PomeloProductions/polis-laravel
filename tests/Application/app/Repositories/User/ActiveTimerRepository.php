<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Contracts\Repositories\User\ActiveTimerRepositoryContract;
use App\Models\User\ActiveTimer;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class ActiveTimerRepository extends BaseRepositoryAbstract implements ActiveTimerRepositoryContract
{
    public function __construct(ActiveTimer $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
