<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Contracts\Repositories\User\TodoTemplateRepositoryContract;
use App\Models\User\TodoTemplate;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class TodoTemplateRepository extends BaseRepositoryAbstract implements TodoTemplateRepositoryContract
{
    public function __construct(TodoTemplate $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
