<?php

declare(strict_types=1);

namespace App\Models\User;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property array|null $days_of_week
 * @property array|null $specific_dates
 * @property bool $is_exclusion
 * @property bool $active_on_vacation
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class TodoCalendar extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasValidationRules, SoftDeletes;

    protected $casts = [
        'days_of_week' => 'array',
        'specific_dates' => 'array',
        'is_exclusion' => 'boolean',
        'active_on_vacation' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taskNodes(): BelongsToMany
    {
        return $this->belongsToMany(TodoTaskNode::class, 'todo_node_calendars')
            ->withPivot('mode', 'sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * Check if this calendar includes the given date.
     *
     * specific_dates entries with a leading "!" are explicit EXCLUSIONS that override
     * the day-of-week pattern (e.g. "!2026-06-19" excludes Juneteenth even though it's a Friday).
     * Plain entries are explicit INCLUSIONS in addition to the day-of-week pattern.
     */
    public function includesDate(Carbon $date): bool
    {
        $ymd = $date->toDateString();

        // Explicit exclusion ("!YYYY-MM-DD") takes precedence over everything.
        if ($this->specific_dates !== null && in_array('!' . $ymd, $this->specific_dates, true)) {
            return false;
        }

        // Day-of-week pattern
        if ($this->days_of_week !== null && in_array($date->dayOfWeek, $this->days_of_week, true)) {
            return true;
        }

        // Explicit inclusion (plain "YYYY-MM-DD")
        if ($this->specific_dates !== null && in_array($ymd, $this->specific_dates, true)) {
            return true;
        }

        return false;
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'name' => ['string', 'max:100'],
                'days_of_week' => ['array', 'nullable'],
                'specific_dates' => ['array', 'nullable'],
                'is_exclusion' => ['boolean'],
                'active_on_vacation' => ['boolean'],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'name',
                ],
            ],
        ];
    }
}
