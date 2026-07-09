<?php

declare(strict_types=1);

namespace Polis\Models\User;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;

/**
 * @property int $id
 * @property int $user_id
 * @property int $todo_balance_id
 * @property int $group_number
 * @property string|null $item_id
 * @property string|null $item_label
 * @property Carbon $occurred_on
 * @property array|null $meta_json
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 * @property-read TodoBalance $balance
 */
class CheckOff extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasValidationRules, SoftDeletes;

    protected $casts = [
        'occurred_on' => 'date',
        'meta_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function balance(): BelongsTo
    {
        return $this->belongsTo(TodoBalance::class, 'todo_balance_id');
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'todo_balance_id' => ['integer', 'exists:todo_balances,id'],
                'group_number' => ['integer', 'min:1'],
                'item_id' => ['string', 'max:100', 'nullable'],
                'item_label' => ['string', 'max:255', 'nullable'],
                'occurred_on' => ['date'],
                'meta_json' => ['json', 'nullable'],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'todo_balance_id',
                    'group_number',
                    'occurred_on',
                ],
            ],
        ];
    }
}
