<?php

declare(strict_types=1);

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;

/**
 * @property int $id
 * @property int $user_id
 * @property string $label
 * @property string|null $note
 * @property int|null $component_id
 * @property string|null $item_id
 * @property float|null $budget_hours
 * @property float|null $session_budget_hours
 * @property int|null $todo_balance_id
 * @property int $session_elapsed_seconds
 * @property Carbon $started_at
 * @property Carbon|null $stopped_at
 * @property int $duration_seconds
 * @property string|null $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 */
class TimeEntry extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasFactory, HasValidationRules, SoftDeletes;

    protected $casts = [
        'component_id' => 'integer',
        'budget_hours' => 'decimal:2',
        'session_budget_hours' => 'decimal:2',
        'todo_balance_id' => 'integer',
        'session_elapsed_seconds' => 'integer',
        'duration_seconds' => 'integer',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timerSession(): BelongsTo
    {
        return $this->belongsTo(TimerSession::class);
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'label' => ['string', 'max:255'],
                'note' => ['string', 'max:255', 'nullable'],
                'started_at' => ['date'],
                'stopped_at' => ['date', 'nullable'],
                'duration_seconds' => ['integer', 'min:0'],
                'color' => ['string', 'max:20', 'nullable'],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'label',
                    'started_at',
                    'duration_seconds',
                ],
            ],
        ];
    }
}
