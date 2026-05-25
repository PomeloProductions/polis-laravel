<?php

declare(strict_types=1);

namespace Polis\Repositories;

use App\Models\Resource;
use Polis\Contracts\Repositories\ResourceRepositoryContract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class ResourceRepository
 */
class ResourceRepository extends BaseRepositoryAbstract implements ResourceRepositoryContract
{
    /**
     * ResourceRepository constructor.
     */
    public function __construct(Resource $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
