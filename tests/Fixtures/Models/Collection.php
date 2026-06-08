<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Collection\Collection.
 *
 * Extends BaseModelAbstract because CollectionRepositoryContract::update()
 * and ::delete() type-hint BaseModelAbstract, and the controller forwards
 * its Collection parameter directly. See Category.php for the shared
 * rationale.
 */
class Collection extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Collection\Collection::class, false)) {
    class_alias(
        Collection::class,
        \App\Models\Collection\Collection::class,
    );
}
