<?php

declare(strict_types=1);

namespace Polis\Repositories\Organization;

use App\Models\Organization\OrganizationManager;
use Polis\Contracts\Repositories\Organization\OrganizationManagerRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class OrganizationManagerRepository
 */
class OrganizationManagerRepository extends BaseRepositoryAbstract implements OrganizationManagerRepositoryContract
{
    /**
     * OrganizationManagerRepository constructor.
     */
    public function __construct(OrganizationManager $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
