<?php

declare(strict_types=1);

namespace Polis\Services\Collection;

use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use App\Models\User\User;
use Polis\Contracts\Models\CanBeMorphedToContract;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Services\Collection\ItemInEntityCollectionServiceContract;

class ItemInEntityCollectionService implements ItemInEntityCollectionServiceContract
{
    /**
     * Tells us whether the passed in item is in any collections a entity has
     *
     * @param  User  $entity
     */
    public function isItemInEntityCollection(IsAnEntityContract $entity, CanBeMorphedToContract $item): bool
    {
        $collectionItems = $entity->collections->flatMap(fn (Collection $i) => $i->collectionItems);

        $maybeCollectionItem = $collectionItems
            ->first(fn (CollectionItem $i) => $i->item_type == $item->morphRelationName() && $i->item_id == $item->id);

        return (bool) $maybeCollectionItem;
    }
}
