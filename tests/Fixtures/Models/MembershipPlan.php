<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Subscription\MembershipPlan.
 *
 * Mirrors the DURATION_LIFETIME constant so ProratingCalculationService
 * branch testing can compare against the real value. AllowDynamicProperties
 * lets tests set current_cost / duration directly on instances without
 * pulling in the full Eloquent base.
 *
 * See User.php for the rationale.
 */
#[\AllowDynamicProperties]
class MembershipPlan
{
    /** Mirror of Polis\Models\Subscription\MembershipPlan::DURATION_LIFETIME. */
    public const DURATION_LIFETIME = 'lifetime';
}

if (! class_exists(\App\Models\Subscription\MembershipPlan::class, false)) {
    class_alias(
        MembershipPlan::class,
        \App\Models\Subscription\MembershipPlan::class,
    );
}
