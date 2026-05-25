<?php

declare(strict_types=1);

namespace Polis\Services;

use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRepositoryContract;
use Polis\Contracts\Services\EntityFeatureAccessServiceContract;

/**
 * Class EntityFeatureAccessService
 */
class EntityFeatureAccessService implements EntityFeatureAccessServiceContract
{
    private MembershipPlanRepositoryContract $membershipPlanRepository;

    /**
     * EntityFeatureAccessService constructor.
     */
    public function __construct(MembershipPlanRepositoryContract $membershipPlanRepository)
    {
        $this->membershipPlanRepository = $membershipPlanRepository;
    }

    /**
     * Tells us whether or not the passed in entity can acess the related feature ID
     */
    public function canAccess(IsAnEntityContract $entity, int $featureId): bool
    {
        $subscription = $entity->currentSubscription();

        if ($subscription) {
            $membershipPlan = $subscription->membershipPlanRate->membershipPlan;
        } else {
            $membershipPlan = $this->membershipPlanRepository->findDefaultMembershipPlanForEntity(
                $entity->morphRelationName()
            );
        }

        return $membershipPlan ? $membershipPlan->features->pluck('id')->contains($featureId) : false;
    }
}
