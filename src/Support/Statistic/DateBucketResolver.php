<?php

declare(strict_types=1);

namespace Polis\Support\Statistic;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Class DateBucketResolver
 *
 * Pure, timezone-aware bucket math for the first-class `date_bucket` axis. Given
 * a timestamp (stored in UTC, as Laravel does), a granularity, a target
 * timezone and — for weekly buckets — a week-start day, it returns a STABLE
 * bucket label.
 *
 * Resolution happens in the configured timezone: the UTC instant is shifted
 * into that zone BEFORE the bucket boundary is computed, so a consumer can
 * bucket in the USER's local tz and a 23:30 UTC event lands in the correct
 * local day/week/month. This is what fixes the downstream midnight-drift bug
 * where buckets were computed against UTC regardless of the user's day.
 *
 * Labels are chosen to sort chronologically as plain strings:
 *   - day:   YYYY-MM-DD      (e.g. 2026-10-27)
 *   - week:  W-YYYY-MM-DD    (the DATE of the configured week-start boundary,
 *            e.g. a Sunday-start week containing 2026-10-27 → "W-2026-10-25").
 *            Labeling by the boundary DATE makes the bucket unambiguous for ANY
 *            week-start day (two different week-starts never collide) and keeps
 *            it chronologically sortable — an ISO-week-NUMBER label cannot, off
 *            Monday.
 *   - month: YYYY-MM         (e.g. 2026-10)
 *   - year:  YYYY            (e.g. 2026)
 *
 * No database or framework state is touched, so this is fully unit-testable.
 */
final class DateBucketResolver
{
    public const DAY = 'day';

    public const WEEK = 'week';

    public const MONTH = 'month';

    public const YEAR = 'year';

    public const GRANULARITIES = [self::DAY, self::WEEK, self::MONTH, self::YEAR];

    /**
     * Resolve the bucket label for an instant.
     *
     * @param  DateTimeInterface|string|int  $instant  A UTC timestamp (Carbon,
     *                                                 ISO string, or unix seconds).
     * @param  string  $granularity  One of day|week|month|year.
     * @param  string  $timezone  The tz to resolve the bucket in (default UTC).
     * @param  int  $weekStartDay  0=Sunday .. 6=Saturday; only used for weekly
     *                             buckets (default Sunday).
     */
    public function bucket(
        DateTimeInterface|string|int $instant,
        string $granularity,
        string $timezone = 'UTC',
        int $weekStartDay = CarbonInterface::SUNDAY,
    ): string {
        if (! in_array($granularity, self::GRANULARITIES, true)) {
            throw new InvalidArgumentException(
                "Unsupported date_bucket granularity [{$granularity}]."
            );
        }

        $local = $this->toLocal($instant, $timezone);

        return match ($granularity) {
            self::DAY => $local->format('Y-m-d'),
            self::MONTH => $local->format('Y-m'),
            self::YEAR => $local->format('Y'),
            self::WEEK => $this->weekLabel($local, $weekStartDay),
        };
    }

    /**
     * Convert the given instant (assumed UTC when no zone is attached) into the
     * target timezone as an immutable Carbon instance.
     */
    private function toLocal(DateTimeInterface|string|int $instant, string $timezone): CarbonImmutable
    {
        if (is_int($instant)) {
            return CarbonImmutable::createFromTimestampUTC($instant)->setTimezone($timezone);
        }

        if ($instant instanceof DateTimeInterface) {
            return CarbonImmutable::instance(
                CarbonImmutable::parse($instant->format(DateTimeInterface::ATOM))
            )->setTimezone($timezone);
        }

        // A bare string is treated as UTC (matching how Laravel stores dates).
        return CarbonImmutable::parse($instant, 'UTC')->setTimezone($timezone);
    }

    /**
     * Compute the weekly bucket label anchored to the configured week-start day.
     *
     * The boundary is the most recent occurrence of $weekStartDay at/ before the
     * local date. The label is the ISO year/week of that boundary date, which
     * keeps labels chronologically sortable while honoring a non-Monday start.
     */
    private function weekLabel(CarbonImmutable $local, int $weekStartDay): string
    {
        $weekStartDay = ($weekStartDay % 7 + 7) % 7;

        $startOfWeek = $local->startOfDay();

        // Walk back to the configured week-start day.
        while ((int) $startOfWeek->dayOfWeek !== $weekStartDay) {
            $startOfWeek = $startOfWeek->subDay();
        }

        // Label by the boundary DATE (plus its ISO week for readability). Using
        // the boundary date — not an ISO week number alone — makes the label
        // unambiguous for any week-start day (two different week-starts never
        // collide) while remaining chronologically sortable, since the trailing
        // YYYY-MM-DD dominates the ordering.
        return $startOfWeek->format('\W-Y-m-d');
    }
}
