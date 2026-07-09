<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\TodoCalendarRepositoryContract;
use Polis\Models\User\TodoCalendar;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TodoCalendarRepository
 */
class TodoCalendarRepository extends BaseRepositoryAbstract implements TodoCalendarRepositoryContract
{
    public function __construct(TodoCalendar $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
