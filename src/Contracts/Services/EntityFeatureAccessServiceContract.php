<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

use Polis\Contracts\Models\IsAnEntityContract;

/**
 * Interface EntityFeatureAccessServiceContract
 */
interface EntityFeatureAccessServiceContract
{
    /**
     * Tells us whether or not the passed in entity can acess the related feature ID
     */
    public function canAccess(IsAnEntityContract $entity, int $featureId): bool;
}
