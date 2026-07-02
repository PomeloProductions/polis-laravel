<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\TimeEntryRepositoryContract;
use Polis\Models\User\TimeEntry;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TimeEntryRepository
 */
class TimeEntryRepository extends BaseRepositoryAbstract implements TimeEntryRepositoryContract
{
    public function __construct(TimeEntry $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
