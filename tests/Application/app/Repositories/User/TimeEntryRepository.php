<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Contracts\Repositories\User\TimeEntryRepositoryContract;
use App\Models\User\TimeEntry;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class TimeEntryRepository extends BaseRepositoryAbstract implements TimeEntryRepositoryContract
{
    public function __construct(TimeEntry $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
