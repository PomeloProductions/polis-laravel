<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Contracts\Repositories\User\TodoSettingRepositoryContract;
use App\Models\User\TodoSetting;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class TodoSettingRepository extends BaseRepositoryAbstract implements TodoSettingRepositoryContract
{
    public function __construct(TodoSetting $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
