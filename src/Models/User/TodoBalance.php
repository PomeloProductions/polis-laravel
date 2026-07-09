<?php

declare(strict_types=1);

namespace Polis\Models\User;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Polis\Contracts\LedgerBalanceContract;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;

/**
 * @property int $id
 * @property int $user_id
 * @property string $item_key
 * @property string $tracking_mode
 * @property float $balance
 * @property float|null $time_budget_hours
 * @property float $tally_step
 * @property array|null $schedule
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 * @property-read Collection|TodoBalanceLog[] $logs
 *
 * The concrete Todo implementation of the generic {@see LedgerBalanceContract}:
 * a running tally keyed by `item_key`, backed by an append-only log chain.
 */
class TodoBalance extends BaseModelAbstract implements HasValidationRulesContract, LedgerBalanceContract
{
    use HasFactory, HasValidationRules, SoftDeletes;

    public const TRACKING_MODE_UNITS = 'units';

    public const TRACKING_MODE_HOURS = 'hours';

    protected $casts = [
        'balance' => 'decimal:4',
        'time_budget_hours' => 'decimal:4',
        'tally_step' => 'decimal:4',
        'schedule' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TodoBalanceLog::class);
    }

    public function ledgerKey(): string
    {
        return $this->item_key;
    }

    public function currentBalance(): float
    {
        return (float) $this->balance;
    }

    public function ledgerLogForeignKey(): string
    {
        return 'todo_balance_id';
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'item_key' => ['string', 'max:255'],
                'tracking_mode' => ['string', 'in:units,hours'],
                'balance' => ['numeric'],
                'time_budget_hours' => ['numeric', 'nullable'],
                'tally_step' => ['numeric'],
                'schedule' => ['array', 'nullable'],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'item_key',
                    'tracking_mode',
                ],
            ],
        ];
    }
}
