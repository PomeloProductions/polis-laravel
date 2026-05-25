<?php

declare(strict_types=1);

namespace Polis\Repositories;

use App\Models\Role;
use Polis\Contracts\Repositories\RoleRepositoryContract;
use Polis\Repositories\Traits\NotImplemented\Create;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class RoleRepository
 */
class RoleRepository extends BaseRepositoryAbstract implements RoleRepositoryContract
{
    use Create,
        \Polis\Repositories\Traits\NotImplemented\Delete,
        \Polis\Repositories\Traits\NotImplemented\FindOrFail,
        \Polis\Repositories\Traits\NotImplemented\Update;

    /**
     * RoleRepository constructor.
     */
    public function __construct(Role $role, LogContract $log)
    {
        parent::__construct($role, $log);
    }
}
