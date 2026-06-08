<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Collection\Collection.
 *
 * CollectionPolicyAbstract / CollectionItemPolicyAbstract type-hint this
 * for view/update/delete. The `owner` property is read directly (not via
 * a method) for the owner-management checks.
 */
class Collection
{
    public ?int $id = null;

    public bool $is_public = false;

    public mixed $owner = null;
}

if (! class_exists(\App\Models\Collection\Collection::class, false)) {
    class_alias(
        Collection::class,
        \App\Models\Collection\Collection::class,
    );
}
