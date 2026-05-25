<?php

declare(strict_types=1);

namespace Polis\Repositories\Organization;

use App\Models\Organization\Organization;
use Polis\Contracts\Repositories\Organization\OrganizationRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class OrganizationRepository
 */
class OrganizationRepository extends BaseRepositoryAbstract implements OrganizationRepositoryContract
{
    /**
     * OrganizationRepository constructor.
     */
    public function __construct(Organization $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
