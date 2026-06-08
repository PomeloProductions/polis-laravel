<?php

declare(strict_types=1);

/**
 * Fixture stub for AdminUI\Laravel\EloquentJoin\Traits\EloquentJoin.
 *
 * The polis-laravel package's BaseModelAbstract uses this trait from
 * the consumer-provided admin-ui/laravel-eloquent-join package, which is
 * NOT listed in this package's composer.json. Without a stub the
 * BaseModelAbstract class cannot be loaded at all in standalone tests —
 * which in turn blocks any test that needs an `instanceof BaseModelAbstract`
 * check (e.g. SubscriptionRepositoryContract::update($model)).
 *
 * Define an empty trait under the expected namespace + register it as a
 * compile-time alias so consumers see "EloquentJoin" even though the real
 * package isn't installed.
 */

namespace Polis\Tests\Fixtures\Vendor;

use AdminUI\Laravel\EloquentJoin\Traits\EloquentJoin;

trait EloquentJoinTrait {}

if (! trait_exists(EloquentJoin::class, false)) {
    class_alias(
        EloquentJoinTrait::class,
        EloquentJoin::class,
    );
}
