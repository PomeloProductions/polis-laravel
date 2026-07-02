<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Polis\Contracts\Repositories\User\TodoTemplateRepositoryContract;
use Polis\Models\User\TodoTemplate;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TodoTemplateRepository
 */
class TodoTemplateRepository extends BaseRepositoryAbstract implements TodoTemplateRepositoryContract
{
    public function __construct(TodoTemplate $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
