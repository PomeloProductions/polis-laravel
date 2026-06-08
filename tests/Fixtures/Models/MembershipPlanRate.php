<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub for App\Models\Subscription\MembershipPlanRate.
 *
 * Extends BaseModelAbstract for parity with the real consumer-app type,
 * so repository tests that pass a rate to methods typed `BaseModelAbstract`
 * (e.g. `MembershipPlanRateRepositoryContract::update($rate, ...)`) work
 * without a multi-class Mockery hack. See MembershipPlan.php for the
 * broader pattern. Eloquent's dynamic attribute getter/setter handles
 * ->cost and other domain fields.
 */
class MembershipPlanRate extends BaseModelAbstract
{
    use \Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Subscription\MembershipPlanRate::class, false)) {
    class_alias(
        MembershipPlanRate::class,
        \App\Models\Subscription\MembershipPlanRate::class,
    );
}
