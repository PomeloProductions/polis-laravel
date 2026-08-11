<?php

declare(strict_types=1);

namespace App\Models\User;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;

/**
 * @property int $id
 * @property int $user_id
 * @property int $todo_balance_id
 * @property string $reason
 * @property float $delta
 * @property float $balance_before
 * @property float $balance_after
 * @property Carbon $occurred_on
 * @property string|null $source_type
 * @property int|null $source_id
 * @property array|null $meta_json
 * @property Carbon|null $created_at
 * @property-read User $user
 * @property-read TodoBalance $todoBalance
 * @property-read \Illuminate\Database\Eloquent\Model|null $source
 */
class TodoBalanceLog extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasValidationRules;

    public const UPDATED_AT = null;

    public const REASON_SEED = 'seed';
    public const REASON_DAILY_INCREMENT = 'daily_increment';
    public const REASON_TIMER_LOGGED = 'timer_logged';
    public const REASON_MANUAL_EDIT = 'manual_edit';
    public const REASON_MARK_DONE = 'mark_done';
    public const REASON_CONVERSION = 'conversion';
    public const REASON_CORRECTION = 'correction';
    public const REASON_TIMER_CREATED = 'timer_created';
    public const REASON_TIMER_UPDATED = 'timer_updated';
    public const REASON_TIMER_DELETED = 'timer_deleted';
    public const REASON_PAGE_EDITED = 'page_edited';

    public const VALID_REASONS = [
        self::REASON_SEED,
        self::REASON_DAILY_INCREMENT,
        self::REASON_TIMER_LOGGED,
        self::REASON_MANUAL_EDIT,
        self::REASON_MARK_DONE,
        self::REASON_CONVERSION,
        self::REASON_CORRECTION,
        self::REASON_TIMER_CREATED,
        self::REASON_TIMER_UPDATED,
        self::REASON_TIMER_DELETED,
        self::REASON_PAGE_EDITED,
    ];

    protected $casts = [
        'delta' => 'decimal:4',
        'balance_before' => 'decimal:4',
        'balance_after' => 'decimal:4',
        'occurred_on' => 'date',
        'meta_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function todoBalance(): BelongsTo
    {
        return $this->belongsTo(TodoBalance::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'reason' => ['string', 'in:' . implode(',', static::VALID_REASONS)],
                'delta' => ['numeric'],
                'balance_before' => ['numeric'],
                'balance_after' => ['numeric'],
                'occurred_on' => ['date'],
                'source_type' => ['string', 'nullable'],
                'source_id' => ['integer', 'nullable'],
                'meta_json' => ['array', 'nullable'],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'reason',
                    'delta',
                    'balance_before',
                    'balance_after',
                    'occurred_on',
                ],
            ],
        ];
    }
}
