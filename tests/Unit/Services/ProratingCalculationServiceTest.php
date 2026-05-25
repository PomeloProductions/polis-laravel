<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use Carbon\Carbon;
use Polis\Services\ProratingCalculationService;
use Polis\Tests\TestCase;

/**
 * Class ProratingCalculationServiceTest
 */
final class ProratingCalculationServiceTest extends TestCase
{
    public function test_calculate_remaining_yearly_charge_when_to_date_is_before_today(): void
    {
        $service = new ProratingCalculationService;

        $result = $service->calculateRemainingYearlyCharge(Carbon::now()->subDays(2), 10, 20);

        $this->assertEquals(0, $result);
    }

    public function test_calculate_remaining_yearly_charge_when_new_rate_is_less_than_old_rate(): void
    {
        $service = new ProratingCalculationService;

        $result = $service->calculateRemainingYearlyCharge(Carbon::now()->addDays(44), 25, 20);

        $this->assertEquals(0, $result);
    }

    public function test_calculate_remaining_yearly_charge_calculates_expected_amount(): void
    {
        $service = new ProratingCalculationService;

        $result = $service->calculateRemainingYearlyCharge(Carbon::now()->addDays(45), 35, 75);

        $this->assertEquals(4.93, $result);
    }

    public function test_calculate_membership_upgrade_charge_with_new_lifetime_with_old_within3_months(): void
    {
        $service = new ProratingCalculationService;

        $currentSubscription = new Subscription([
            'subscribed_at' => Carbon::now()->subMonths(2),
            'membershipPlanRate' => new MembershipPlanRate([
                'cost' => 75,
            ]),
        ]);

        $newMembershipPlan = new MembershipPlan([
            'duration' => MembershipPlan::DURATION_LIFETIME,
            'currentRate' => new MembershipPlanRate([
                'active' => true,
                'cost' => 500,
            ]),
        ]);

        $result = $service->calculateMembershipUpgradeCharge($currentSubscription, $newMembershipPlan);

        $this->assertEquals(425, $result);
    }

    public function test_calculate_membership_upgrade_charge_with_new_lifetime_with_old_past3_months(): void
    {
        $service = new ProratingCalculationService;

        $currentSubscription = new Subscription([
            'subscribed_at' => Carbon::now()->subMonths(5),
            'membershipPlanRate' => new MembershipPlanRate([
                'cost' => 75,
            ]),
        ]);

        $newMembershipPlan = new MembershipPlan([
            'duration' => MembershipPlan::DURATION_LIFETIME,
            'currentRate' => new MembershipPlanRate([
                'active' => true,
                'cost' => 500,
            ]),
        ]);

        $result = $service->calculateMembershipUpgradeCharge($currentSubscription, $newMembershipPlan);

        $this->assertEquals(500, $result);
    }

    public function test_calculate_membership_upgrade_charge_with_yearly_plan(): void
    {
        $service = new ProratingCalculationService;

        $currentSubscription = new Subscription([
            'expires_at' => Carbon::now()->addDays(45),
            'membershipPlanRate' => new MembershipPlanRate([
                'cost' => 35,
            ]),
        ]);

        $newMembershipPlan = new MembershipPlan([
            'duration' => MembershipPlan::DURATION_YEAR,
            'currentRate' => new MembershipPlanRate([
                'active' => true,
                'cost' => 75,
            ]),
        ]);

        $result = $service->calculateMembershipUpgradeCharge($currentSubscription, $newMembershipPlan);

        $this->assertEquals(4.93, $result);
    }
}
