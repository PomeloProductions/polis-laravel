<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use Illuminate\Support\Carbon;
use Polis\Models\User\TodoVacationPeriod;
use Polis\Tests\TestCase;

/**
 * coversDate must compare CALENDAR DATES, not instants. Callers pass dates built in the user's
 * timezone while start/end are stored as plain UTC dates — instant comparison shifts the boundary
 * by the UTC offset. Regression: for a UTC+2 user, midnight on the day AFTER end_date was still
 * 22:00 UTC on end_date, so the first post-vacation day was treated as vacation and the daily
 * increment was suppressed.
 */
final class TodoVacationPeriodTest extends TestCase
{
    private function period(string $start, ?string $end): TodoVacationPeriod
    {
        return new TodoVacationPeriod(['start_date' => $start, 'end_date' => $end]);
    }

    public function test_end_date_is_inclusive_and_next_day_is_not_covered_in_ahead_of_utc_timezone(): void
    {
        // UTC+2 — the timezone where the regression occurred.
        $period = $this->period('2026-07-04', '2026-07-09');

        $this->assertFalse($period->coversDate(Carbon::parse('2026-07-03', 'Europe/Berlin')->startOfDay()));
        $this->assertTrue($period->coversDate(Carbon::parse('2026-07-04', 'Europe/Berlin')->startOfDay()));
        $this->assertTrue($period->coversDate(Carbon::parse('2026-07-09', 'Europe/Berlin')->startOfDay()));
        // The regression: this returned true (07-10 00:00 Berlin = 07-09 22:00 UTC).
        $this->assertFalse($period->coversDate(Carbon::parse('2026-07-10', 'Europe/Berlin')->startOfDay()));
    }

    public function test_boundaries_in_behind_utc_timezone(): void
    {
        $period = $this->period('2026-07-04', '2026-07-09');

        $this->assertFalse($period->coversDate(Carbon::parse('2026-07-03', 'America/Los_Angeles')->startOfDay()));
        $this->assertTrue($period->coversDate(Carbon::parse('2026-07-04', 'America/Los_Angeles')->startOfDay()));
        $this->assertTrue($period->coversDate(Carbon::parse('2026-07-09', 'America/Los_Angeles')->startOfDay()));
        $this->assertFalse($period->coversDate(Carbon::parse('2026-07-10', 'America/Los_Angeles')->startOfDay()));
    }

    public function test_open_ended_period_covers_everything_from_start(): void
    {
        $period = $this->period('2026-07-04', null);

        $this->assertFalse($period->coversDate(Carbon::parse('2026-07-03', 'Europe/Berlin')->startOfDay()));
        $this->assertTrue($period->coversDate(Carbon::parse('2026-07-04', 'Europe/Berlin')->startOfDay()));
        $this->assertTrue($period->coversDate(Carbon::parse('2027-01-01', 'Europe/Berlin')->startOfDay()));
    }

    public function test_date_is_vacation_checks_all_periods(): void
    {
        $periods = collect([
            $this->period('2026-07-04', '2026-07-09'),
            $this->period('2026-08-01', null),
        ]);

        $this->assertTrue(TodoVacationPeriod::dateIsVacation($periods, Carbon::parse('2026-07-06', 'Europe/Berlin')->startOfDay()));
        $this->assertFalse(TodoVacationPeriod::dateIsVacation($periods, Carbon::parse('2026-07-15', 'Europe/Berlin')->startOfDay()));
        $this->assertTrue(TodoVacationPeriod::dateIsVacation($periods, Carbon::parse('2026-08-02', 'Europe/Berlin')->startOfDay()));
    }
}
