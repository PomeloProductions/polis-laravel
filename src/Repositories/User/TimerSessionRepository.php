<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\TimerSessionRepositoryContract;
use Polis\Models\User\TimerSession;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TimerSessionRepository
 */
class TimerSessionRepository extends BaseRepositoryAbstract implements TimerSessionRepositoryContract
{
    public function __construct(TimerSession $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
