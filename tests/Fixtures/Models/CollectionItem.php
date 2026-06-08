<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Collection\CollectionItem.
 *
 * Extends BaseModelAbstract because CollectionItemRepositoryContract::delete()
 * type-hints BaseModelAbstract. See Category.php for the shared rationale.
 */
class CollectionItem extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Collection\CollectionItem::class, false)) {
    class_alias(
        CollectionItem::class,
        \App\Models\Collection\CollectionItem::class,
    );
}
