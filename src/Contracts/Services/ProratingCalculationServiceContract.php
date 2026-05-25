<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\Subscription;
use Carbon\Carbon;

/**
 * Interface ProratingCalculationServiceContract
 */
interface ProratingCalculationServiceContract
{
    /**
     * Calculates how much is remaining to be charged prorating to change a plan from one to another for a remaining term
     */
    public function calculateRemainingYearlyCharge(Carbon $toDate, float $amountPaid, float $newYearlyAmount): float;

    /**
     * Calculates how much it will cost to upgrade from the current subscription to the new membership plan
     */
    public function calculateMembershipUpgradeCharge(Subscription $currentSubscription, MembershipPlan $newMembershipPlan): float;
}
