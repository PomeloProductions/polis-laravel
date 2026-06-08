<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Collection\CollectionItem.
 *
 * Used in CollectionItemPolicyAbstract view/update/delete gates.
 */
class CollectionItem
{
    public ?int $id = null;

    public ?int $collection_id = null;

    public ?string $item_type = null;

    public ?int $item_id = null;
}

if (! class_exists(\App\Models\Collection\CollectionItem::class, false)) {
    class_alias(
        CollectionItem::class,
        \App\Models\Collection\CollectionItem::class,
    );
}
