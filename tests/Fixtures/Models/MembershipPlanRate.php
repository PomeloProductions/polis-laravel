<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Subscription\MembershipPlanRate.
 *
 * AllowDynamicProperties so tests can populate ->cost (and any other
 * domain-specific fields) directly on instances. See User.php.
 */
#[\AllowDynamicProperties]
class MembershipPlanRate {}

if (! class_exists(\App\Models\Subscription\MembershipPlanRate::class, false)) {
    class_alias(
        MembershipPlanRate::class,
        \App\Models\Subscription\MembershipPlanRate::class,
    );
}
