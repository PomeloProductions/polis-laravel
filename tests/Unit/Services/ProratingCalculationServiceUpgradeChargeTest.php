<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use Carbon\Carbon;
use Mockery;
use Polis\Services\ProratingCalculationService;
use Polis\Tests\Fixtures\Models\MembershipPlan;
use Polis\Tests\Fixtures\Models\MembershipPlanRate;
use Polis\Tests\Fixtures\Models\Subscription;
use Polis\Tests\TestCase;

/**
 * Standalone coverage for ProratingCalculationService::calculateMembershipUpgradeCharge.
 *
 * This was previously skipped in the existing standalone test because it
 * required concrete Subscription / MembershipPlan / MembershipPlanRate
 * instances. The Subscription fixture (PR #11) extends the real
 * BaseModelAbstract, so we can populate setRawAttributes() to satisfy
 * the property reads the service performs (->is_trial, ->subscribed_at,
 * ->expires_at, ->membershipPlanRate). Plan / Rate are plain fixtures
 * with dynamic property assignment.
 *
 * The five branches we cover here mirror the service's control flow:
 *   1. New plan cost == 0 -> 0.
 *   2. Subscription is_trial -> full new cost.
 *   3. Lifetime upgrade within 90 days -> cost - already paid.
 *   4. Lifetime upgrade after 90 days -> full new cost.
 *   5. Non-lifetime upgrade with expires_at -> prorated remaining yearly.
 *   6. Non-lifetime upgrade without expires_at -> full new cost.
 */
final class ProratingCalculationServiceUpgradeChargeTest extends TestCase
{
    private function makeRate(float $cost): MembershipPlanRate
    {
        $rate = new MembershipPlanRate;
        // MembershipPlanRate is a plain fixture (#[AllowDynamicProperties] not
        // needed because we only set declared/dynamic-allowed slots here).
        $rate->cost = $cost;

        return $rate;
    }

    public function test_returns_zero_when_new_plan_cost_is_zero(): void
    {
        $service = new ProratingCalculationService;

        $newPlan = new MembershipPlan;
        $newPlan->current_cost = 0;

        // The service reads membershipPlanRate->cost upfront, before the
        // zero check. Provide a rate so the property access succeeds, then
        // the cost==0 branch returns 0 regardless of what the rate is.
        $sub = new Subscription;
        $sub->setRelation('membershipPlanRate', $this->makeRate(50.0));

        $this->assertSame(0.0, $service->calculateMembershipUpgradeCharge($sub, $newPlan));
    }

    public function test_returns_full_cost_when_subscription_is_trial(): void
    {
        $service = new ProratingCalculationService;

        $newPlan = new MembershipPlan;
        $newPlan->current_cost = 99.0;

        $rate = $this->makeRate(50.0);
        $sub = new Subscription;
        $sub->is_trial = true;
        $sub->setRelation('membershipPlanRate', $rate);

        $this->assertSame(99.0, $service->calculateMembershipUpgradeCharge($sub, $newPlan));
    }

    public function test_lifetime_upgrade_within_90_days_credits_existing_cost(): void
    {
        $service = new ProratingCalculationService;

        $newPlan = new MembershipPlan;
        $newPlan->current_cost = 200.0;
        $newPlan->duration = MembershipPlan::DURATION_LIFETIME;

        $rate = $this->makeRate(50.0);
        $sub = new Subscription;
        $sub->is_trial = false;
        // The fixture Subscription has no datetime casts (the production
        // Subscription does); pass Carbon objects directly so the service's
        // diffInDays() receives a Carbon, not a raw string.
        $sub->subscribed_at = Carbon::now()->subDays(30);
        $sub->setRelation('membershipPlanRate', $rate);

        // oldLength = 30 days, <= 90 -> newCost - currentSubscriptionCost.
        $this->assertSame(150.0, $service->calculateMembershipUpgradeCharge($sub, $newPlan));
    }

    public function test_lifetime_upgrade_after_90_days_returns_full_cost(): void
    {
        $service = new ProratingCalculationService;

        $newPlan = new MembershipPlan;
        $newPlan->current_cost = 200.0;
        $newPlan->duration = MembershipPlan::DURATION_LIFETIME;

        $rate = $this->makeRate(50.0);
        $sub = new Subscription;
        $sub->is_trial = false;
        $sub->subscribed_at = Carbon::now()->subDays(120);
        $sub->setRelation('membershipPlanRate', $rate);

        $this->assertSame(200.0, $service->calculateMembershipUpgradeCharge($sub, $newPlan));
    }

    public function test_non_lifetime_with_expires_at_uses_prorated_remainder(): void
    {
        $service = new ProratingCalculationService;

        $newPlan = new MembershipPlan;
        $newPlan->current_cost = 75.0;
        $newPlan->duration = 'yearly';

        $rate = $this->makeRate(35.0);
        $sub = new Subscription;
        $sub->is_trial = false;
        $sub->expires_at = Carbon::now()->addDays(45);
        $sub->setRelation('membershipPlanRate', $rate);

        // Mirrors test_calculates_prorated_remainder_for_upgrade in the
        // existing standalone test: 45 * (75 - 35) / 365 = 4.93.
        $this->assertEquals(4.93, $service->calculateMembershipUpgradeCharge($sub, $newPlan));
    }

    public function test_non_lifetime_without_expires_at_returns_full_cost(): void
    {
        $service = new ProratingCalculationService;

        $newPlan = new MembershipPlan;
        $newPlan->current_cost = 75.0;
        $newPlan->duration = 'yearly';

        $rate = $this->makeRate(35.0);
        $sub = new Subscription;
        $sub->is_trial = false;
        $sub->expires_at = null;
        $sub->setRelation('membershipPlanRate', $rate);

        $this->assertSame(75.0, $service->calculateMembershipUpgradeCharge($sub, $newPlan));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
