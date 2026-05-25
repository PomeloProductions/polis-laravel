<?php

declare(strict_types=1);

namespace Polis\Repositories\Subscription;

use App\Models\Subscription\MembershipPlanRate;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRateRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class MembershipPlanRateRepository
 */
class MembershipPlanRateRepository extends BaseRepositoryAbstract implements MembershipPlanRateRepositoryContract
{
    /**
     * MembershipPlanRateRepository constructor.
     */
    public function __construct(MembershipPlanRate $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
