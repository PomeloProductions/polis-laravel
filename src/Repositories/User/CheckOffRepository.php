<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\CheckOffRepositoryContract;
use Polis\Models\User\CheckOff;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class CheckOffRepository
 */
class CheckOffRepository extends BaseRepositoryAbstract implements CheckOffRepositoryContract
{
    public function __construct(CheckOff $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
