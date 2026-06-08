<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub for App\Models\Subscription\Subscription.
 *
 * Extends BaseModelAbstract because SubscriptionRepositoryContract::update()
 * requires its first argument to be a BaseModelAbstract instance — the
 * Subscription fixture is therefore the one App\Models\* alias that does
 * need to inherit the real Polis base. The
 * tests/Fixtures/Vendor/EloquentJoinTrait.php fixture stubs out the
 * BaseModelAbstract's missing AdminUI trait so the inheritance can
 * resolve in this package's standalone harness.
 *
 * Eloquent intercepts property assignment via setAttribute, so when tests
 * set `$subscription->subscriber = $mock` it routes through the model's
 * attribute store rather than a dynamic property. That's fine for our
 * purposes — Eloquent's __get falls back to the attributes array when no
 * matching relation/method exists, so reads continue to work.
 */
class Subscription extends BaseModelAbstract
{
    use \Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Subscription\Subscription::class, false)) {
    class_alias(
        Subscription::class,
        \App\Models\Subscription\Subscription::class,
    );
}
