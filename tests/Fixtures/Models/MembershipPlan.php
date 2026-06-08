<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub for App\Models\Subscription\MembershipPlan.
 *
 * Extends BaseModelAbstract so that repository tests calling the rate
 * repository's `create($data, $relatedModel)` — typed as
 * `?Polis\Models\BaseModelAbstract` — can pass a Mockery double of this
 * fixture as the parent model. (Mockery's multi-class hack does not work
 * with two concrete classes; the only way to satisfy both type hints is
 * to make MembershipPlan an actual BaseModelAbstract descendant.)
 *
 * Mirrors the DURATION_* constants from the real consumer-app model so
 * that ProratingCalculationService branch testing and
 * SubscriptionRepository::create() comparisons see the same values.
 *
 * See User.php for the broader fixture rationale.
 */
class MembershipPlan extends BaseModelAbstract
{
    use \Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

    /** Mirror of Polis\Models\Subscription\MembershipPlan::DURATION_LIFETIME. */
    public const DURATION_LIFETIME = 'lifetime';

    public const DURATION_MONTH = 'month';

    public const DURATION_YEAR = 'year';
}

if (! class_exists(\App\Models\Subscription\MembershipPlan::class, false)) {
    class_alias(
        MembershipPlan::class,
        \App\Models\Subscription\MembershipPlan::class,
    );
}
