<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Subscription;

use App\Models\User\User;
use App\Policies\Subscription\MembershipPlanRatePolicy;
use Polis\Tests\TestCase;

/**
 * Class MembershipPlanRatePolicyTest
 */
final class MembershipPlanRatePolicyTest extends TestCase
{
    public function test_all(): void
    {
        $policy = new MembershipPlanRatePolicy;
        $this->assertFalse($policy->all(new User));
    }
}
