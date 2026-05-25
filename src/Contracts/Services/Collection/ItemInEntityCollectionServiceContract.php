<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Collection;

use Polis\Contracts\Models\CanBeMorphedToContract;
use Polis\Contracts\Models\IsAnEntityContract;

interface ItemInEntityCollectionServiceContract
{
    /**
     * Tells us whether the passed in item is in any collections a entity has
     */
    public function isItemInEntityCollection(IsAnEntityContract $entity, CanBeMorphedToContract $item): bool;
}
