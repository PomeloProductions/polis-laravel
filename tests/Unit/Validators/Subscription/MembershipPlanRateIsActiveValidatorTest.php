<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validators\Subscription;

use App\Models\Subscription\MembershipPlanRate;
use Cartalyst\Stripe\Exception\NotFoundException;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRateRepositoryContract;
use Polis\Tests\TestCase;
use Polis\Validators\Subscription\MembershipPlanRateIsActiveValidator;

/**
 * Class MembershipPlanRateIsActiveValidatorTest
 */
final class MembershipPlanRateIsActiveValidatorTest extends TestCase
{
    public function test_validate_fails_with_non_existing_rate(): void
    {
        $repository = mock(MembershipPlanRateRepositoryContract::class);
        $validator = new MembershipPlanRateIsActiveValidator($repository);

        $repository->shouldReceive('findOrFail')->andThrow(new NotFoundException);

        $this->assertFalse($validator->validate('membership_plan_rate_id', 214));
    }

    public function test_validate_fails_membership_plan_rate_not_active(): void
    {
        $repository = mock(MembershipPlanRateRepositoryContract::class);
        $validator = new MembershipPlanRateIsActiveValidator($repository);

        $membershipPlanRate = new MembershipPlanRate([
            'active' => false,
        ]);
        $repository->shouldReceive('findOrFail')->andReturn($membershipPlanRate);

        $this->assertFalse($validator->validate('membership_plan_rate_id', 214));
    }

    public function test_validate_success(): void
    {
        $repository = mock(MembershipPlanRateRepositoryContract::class);
        $validator = new MembershipPlanRateIsActiveValidator($repository);

        $membershipPlanRate = new MembershipPlanRate([
            'active' => true,
        ]);
        $repository->shouldReceive('findOrFail')->andReturn($membershipPlanRate);

        $this->assertTrue($validator->validate('membership_plan_rate_id', 214));
    }
}
