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
 * @property int $component_id
 * @property string $item_id
 * @property string $label
 * @property float $budget_hours
 * @property float|null $session_budget_hours
 * @property Carbon $started_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 */
class ActiveTimer extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasFactory, HasValidationRules, SoftDeletes;

    protected $casts = [
        'component_id' => 'integer',
        'budget_hours' => 'float',
        'session_budget_hours' => 'float',
        'started_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'component_id' => ['integer'],
                'item_id' => ['string', 'max:100'],
                'label' => ['string', 'max:255'],
                'budget_hours' => ['numeric', 'min:0'],
                'session_budget_hours' => ['numeric', 'min:0', 'nullable'],
                'started_at' => ['date'],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'component_id',
                    'item_id',
                    'label',
                    'started_at',
                ],
            ],
        ];
    }
}
