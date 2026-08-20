<?php

declare(strict_types=1);

namespace Polis\Models\User;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;

/**
 * A span of vacation days. end_date null means the vacation is currently ongoing.
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class TodoVacationPeriod extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasValidationRules, SoftDeletes;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Does this period cover the given date? Inclusive on both ends; an open period
     * (null end_date) covers everything from start_date onward.
     */
    public function coversDate(Carbon $date): bool
    {
        // Compare CALENDAR DATES (Y-m-d strings), not instants: callers pass dates in the
        // user's timezone while start/end are stored as plain UTC dates, so instant comparison
        // shifts the boundary by the UTC offset (e.g. for UTC+2, midnight on the day AFTER
        // end_date is still 22:00 UTC on end_date — which wrongly extended vacations by a day
        // and suppressed the first post-vacation increment).
        $ymd = $date->toDateString();

        if ($ymd < $this->start_date->toDateString()) {
            return false;
        }

        return $this->end_date === null
            || $ymd <= $this->end_date->toDateString();
    }

    /**
     * Is the given date a vacation day for this user, given a preloaded set of periods?
     *
     * @param  Collection<int, TodoVacationPeriod>  $periods
     */
    public static function dateIsVacation(Collection $periods, Carbon $date): bool
    {
        foreach ($periods as $period) {
            if ($period->coversDate($date)) {
                return true;
            }
        }

        return false;
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'start_date' => ['date'],
                'end_date' => ['date', 'nullable'],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'start_date',
                ],
            ],
        ];
    }
}
