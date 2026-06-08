<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Subscription\MembershipPlan.
 *
 * See User.php for the rationale.
 */
class MembershipPlan {}

if (! class_exists(\App\Models\Subscription\MembershipPlan::class, false)) {
    class_alias(
        MembershipPlan::class,
        \App\Models\Subscription\MembershipPlan::class,
    );
}
