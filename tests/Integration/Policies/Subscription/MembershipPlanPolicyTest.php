<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Subscription;

use App\Models\Subscription\MembershipPlan;
use App\Models\User\User;
use App\Policies\Subscription\MembershipPlanPolicy;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class MembershipPlanPolicyTest
 */
final class MembershipPlanPolicyTest extends ApplicationTestCase
{
    
    public function test_all(): void
    {
        $policy = new MembershipPlanPolicy;

        $this->assertTrue($policy->all(new User));
    }

    public function test_view(): void
    {
        $policy = new MembershipPlanPolicy;

        $this->assertTrue($policy->view(new User, new MembershipPlan));
    }

    public function test_create(): void
    {
        $policy = new MembershipPlanPolicy;

        $this->assertFalse($policy->create(new User));
    }

    public function test_update(): void
    {
        $policy = new MembershipPlanPolicy;

        $this->assertFalse($policy->update(new User, new MembershipPlan));
    }

    public function test_delete(): void
    {
        $policy = new MembershipPlanPolicy;

        $this->assertFalse($policy->delete(new User, new MembershipPlan));
    }
}
