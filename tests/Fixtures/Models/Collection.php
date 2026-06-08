<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub for App\Models\Collection\Collection.
 *
 * Extends BaseModelAbstract so CollectionRepository tests can pass
 * Collection mocks to update()/syncChildModels(). Mixes in
 * MockeryFriendlyAttributesTrait so legacy policy tests' `$mock->id = 5`
 * patterns continue to work on Mockery doubles. See User.php for the
 * broader fixture rationale.
 *
 * CollectionPolicyAbstract / CollectionItemPolicyAbstract type-hint this
 * for view/update/delete. The `owner` property is read directly (not via
 * a method) for the owner-management checks — Eloquent's attribute getter
 * handles that on real instances; legacy policy tests assign it via the
 * trait's __set fast-path.
 */
class Collection extends BaseModelAbstract
{
    use \Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Collection\Collection::class, false)) {
    class_alias(
        Collection::class,
        \App\Models\Collection\Collection::class,
    );
}
