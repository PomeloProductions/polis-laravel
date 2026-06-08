<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Category.
 *
 * CategoryPolicyAbstract type-hints this on update/delete (both gates
 * return false at the abstract level — only super-admins pass via the
 * BasePolicyAbstract::before() bypass).
 */
class Category
{
    public ?int $id = null;
}

if (! class_exists(\App\Models\Category::class, false)) {
    class_alias(
        Category::class,
        \App\Models\Category::class,
    );
}
