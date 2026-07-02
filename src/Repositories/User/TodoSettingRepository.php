<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\TodoSettingRepositoryContract;
use Polis\Models\User\TodoSetting;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TodoSettingRepository
 */
class TodoSettingRepository extends BaseRepositoryAbstract implements TodoSettingRepositoryContract
{
    public function __construct(TodoSetting $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
