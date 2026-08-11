<?php

declare(strict_types=1);

namespace App\Services\Todo;

use App\Models\User\TodoCalendar;
use App\Models\User\TodoTaskNode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CalendarResolver
{
    /**
     * Check if a node is scheduled on a given date.
     * Uses calendar rules if attached, falls back to legacy schedule array.
     */
    public static function isScheduled(TodoTaskNode $node, Carbon $date): bool
    {
        // Load calendars if not already loaded
        if (!$node->relationLoaded('calendars')) {
            $node->load('calendars');
        }

        $calendars = $node->calendars;

        // No calendars attached → fall back to legacy schedule or all days
        if ($calendars->isEmpty()) {
            $schedule = $node->schedule;
            if ($schedule === null) {
                return true; // All days
            }
            return in_array($date->dayOfWeek, $schedule, true);
        }

        return self::resolveCalendars($calendars, $date);
    }

    /**
     * Check if a date is scheduled given a collection of calendars with pivot mode.
     */
    public static function resolveCalendars(Collection $calendars, Carbon $date): bool
    {
        $included = false;

        // First pass: add calendars
        foreach ($calendars as $calendar) {
            if ($calendar->pivot->mode === 'add') {
                if ($calendar->includesDate($date)) {
                    $included = true;
                }
            }
        }

        // If no "add" calendars matched, not scheduled
        if (!$included) {
            return false;
        }

        // Second pass: subtract calendars
        foreach ($calendars as $calendar) {
            if ($calendar->pivot->mode === 'subtract') {
                if ($calendar->includesDate($date)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Given the calendars that schedule a node, is the node's scheduling on $date "active on
     * vacation"? True if at least one "add" calendar that includes $date is flagged
     * active_on_vacation. When false, the node's daily increment should be suppressed on a
     * vacation day. Callers should only consult this for dates already known to be scheduled.
     */
    public static function anyActiveOnVacation(Collection $calendars, Carbon $date): bool
    {
        foreach ($calendars as $calendar) {
            if ($calendar->pivot->mode === 'add'
                && $calendar->includesDate($date)
                && $calendar->active_on_vacation) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the effective day-of-week array for a node's calendars (for legacy compat).
     * Only considers day-of-week patterns, not specific dates.
     */
    public static function resolveDaysOfWeek(Collection $calendars): ?array
    {
        if ($calendars->isEmpty()) {
            return null;
        }

        $days = [];

        foreach ($calendars as $calendar) {
            if ($calendar->pivot->mode === 'add' && $calendar->days_of_week) {
                $days = array_merge($days, $calendar->days_of_week);
            }
        }

        $days = array_unique($days);

        foreach ($calendars as $calendar) {
            if ($calendar->pivot->mode === 'subtract' && $calendar->days_of_week) {
                $days = array_diff($days, $calendar->days_of_week);
            }
        }

        sort($days);
        return array_values($days);
    }
}
