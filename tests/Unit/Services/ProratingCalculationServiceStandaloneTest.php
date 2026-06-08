<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use Carbon\Carbon;
use Polis\Services\ProratingCalculationService;
use Polis\Tests\TestCase;

/**
 * Standalone-runnable coverage for ProratingCalculationService::calculateRemainingYearlyCharge.
 * The calculateMembershipUpgradeCharge method requires concrete App\Models\Subscription\*
 * fixtures and stays in the Consumer-Only suite.
 */
final class ProratingCalculationServiceStandaloneTest extends TestCase
{
    public function test_returns_zero_when_to_date_is_in_the_past(): void
    {
        $service = new ProratingCalculationService;

        $this->assertSame(
            0.0,
            (float) $service->calculateRemainingYearlyCharge(Carbon::now()->subDays(2), 10, 20),
        );
    }

    public function test_returns_zero_when_new_rate_is_lower_than_old_rate(): void
    {
        $service = new ProratingCalculationService;

        $this->assertSame(
            0.0,
            (float) $service->calculateRemainingYearlyCharge(Carbon::now()->addDays(44), 25, 20),
        );
    }

    public function test_returns_zero_when_new_rate_equals_old_rate(): void
    {
        $service = new ProratingCalculationService;

        $this->assertSame(
            0.0,
            (float) $service->calculateRemainingYearlyCharge(Carbon::now()->addDays(44), 50, 50),
        );
    }

    public function test_calculates_prorated_remainder_for_upgrade(): void
    {
        $service = new ProratingCalculationService;

        // 45 days * (75 - 35) / 365 = 4.9315... rounded to 4.93.
        $this->assertEquals(
            4.93,
            $service->calculateRemainingYearlyCharge(Carbon::now()->addDays(45), 35, 75),
        );
    }
}
