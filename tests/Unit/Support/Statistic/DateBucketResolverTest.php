<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Support\Statistic;

use Carbon\CarbonInterface;
use InvalidArgumentException;
use Polis\Support\Statistic\DateBucketResolver;
use Polis\Tests\TestCase;

/**
 * Class DateBucketResolverTest
 *
 * Unit coverage for bucket boundary math: day/week/month/year labels,
 * timezone-aware resolution, and configurable week-start boundaries.
 */
final class DateBucketResolverTest extends TestCase
{
    private DateBucketResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new DateBucketResolver;
    }

    public function test_day_bucket(): void
    {
        $this->assertSame('2026-10-27', $this->resolver->bucket('2026-10-27 13:30:00', 'day'));
    }

    public function test_month_and_year_buckets(): void
    {
        $this->assertSame('2026-10', $this->resolver->bucket('2026-10-27 13:30:00', 'month'));
        $this->assertSame('2026', $this->resolver->bucket('2026-10-27 13:30:00', 'year'));
    }

    public function test_timezone_shifts_the_day(): void
    {
        // 03:00 UTC on Oct 2 is 23:00 Oct 1 in America/New_York (UTC-4).
        $this->assertSame(
            '2026-10-01',
            $this->resolver->bucket('2026-10-02 03:00:00', 'day', 'America/New_York'),
        );
    }

    public function test_utc_day_is_unchanged_without_tz(): void
    {
        $this->assertSame('2026-10-02', $this->resolver->bucket('2026-10-02 03:00:00', 'day'));
    }

    public function test_week_bucket_honors_sunday_start(): void
    {
        // 2026-10-27 is a Tuesday. With a Sunday week-start the boundary is
        // 2026-10-25 (the preceding Sunday) and all days in that week share a
        // label.
        $tuesday = $this->resolver->bucket('2026-10-27', 'week', 'UTC', CarbonInterface::SUNDAY);
        $sunday = $this->resolver->bucket('2026-10-25', 'week', 'UTC', CarbonInterface::SUNDAY);
        $saturday = $this->resolver->bucket('2026-10-31', 'week', 'UTC', CarbonInterface::SUNDAY);

        $this->assertSame($sunday, $tuesday);
        $this->assertSame($sunday, $saturday);
    }

    public function test_week_bucket_honors_monday_start(): void
    {
        // With a Monday start, Sunday 2026-10-25 belongs to the PREVIOUS week,
        // while Monday 2026-10-26 starts a new one.
        $sunday = $this->resolver->bucket('2026-10-25', 'week', 'UTC', CarbonInterface::MONDAY);
        $monday = $this->resolver->bucket('2026-10-26', 'week', 'UTC', CarbonInterface::MONDAY);

        $this->assertNotSame($sunday, $monday);
    }

    public function test_week_start_day_changes_the_boundary(): void
    {
        // The same date can fall in different week buckets depending on the start
        // day; a Sunday-start and a Monday-start must not always agree.
        $sundayStart = $this->resolver->bucket('2026-10-25', 'week', 'UTC', CarbonInterface::SUNDAY);
        $mondayStart = $this->resolver->bucket('2026-10-25', 'week', 'UTC', CarbonInterface::MONDAY);

        $this->assertNotSame($sundayStart, $mondayStart);
    }

    public function test_unsupported_granularity_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->resolver->bucket('2026-10-27', 'fortnight');
    }

    public function test_accepts_unix_timestamp(): void
    {
        // 2026-10-27 00:00:00 UTC.
        $ts = strtotime('2026-10-27 00:00:00 UTC');
        $this->assertSame('2026-10-27', $this->resolver->bucket($ts, 'day'));
    }
}
