<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\Subscription;

use App\Models\Subscription\MembershipPlan;
use Polis\Contracts\Repositories\BaseRepositoryContract;

/**
 * Interface MembershipPlanRepositoryContract
 */
interface MembershipPlanRepositoryContract extends BaseRepositoryContract
{
    /**
     * Finds the default membership plan that will be applied to an entity if the entity is not subscribed
     */
    public function findDefaultMembershipPlanForEntity(string $entityType): ?MembershipPlan;
}
