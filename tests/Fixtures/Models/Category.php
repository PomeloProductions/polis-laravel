<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Category.
 *
 * Extends BaseModelAbstract because CategoryRepositoryContract::delete()
 * and ::update() type-hint BaseModelAbstract on their first argument; a
 * parentless stub wouldn't pass that constraint when the controller
 * forwards the App\Models\Category through to the repo.
 *
 * Eloquent's __set magic routes property assignment through setAttribute()
 * rather than dynamic-property writes, but for our purposes the
 * controllers only ever *read* attributes on these models (e.g.
 * $page->is_required) — and Eloquent's __get falls back to the attributes
 * array, so Mockery-style mocks continue to behave.
 */
class Category extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Category::class, false)) {
    class_alias(
        Category::class,
        \App\Models\Category::class,
    );
}
