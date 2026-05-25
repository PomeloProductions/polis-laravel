<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

use App\Models\Subscription\Subscription;
use Polis\Contracts\Models\IsAnEntityContract;

/**
 * Interface EntitySubscriptionCreationService
 */
interface EntitySubscriptionCreationServiceContract
{
    /**
     * Creates a subscription for an entity while replacing any current one that may exist for an entity
     */
    public function createSubscription(IsAnEntityContract $entity, array $data): Subscription;
}
