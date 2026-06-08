<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Subscription\MembershipPlanRate.
 *
 * See User.php for the rationale.
 */
class MembershipPlanRate {}

if (! class_exists(\App\Models\Subscription\MembershipPlanRate::class, false)) {
    class_alias(
        MembershipPlanRate::class,
        \App\Models\Subscription\MembershipPlanRate::class,
    );
}
