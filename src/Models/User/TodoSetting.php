<?php

declare(strict_types=1);

namespace Polis\Models\User;

use App\Models\User\User;
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
 * @property int $week_start_day
 * @property string $timezone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 */
class TodoSetting extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasFactory, HasValidationRules, SoftDeletes;

    protected $casts = [
        'week_start_day' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'week_start_day' => [
                    'integer',
                    'min:0',
                    'max:6',
                ],
                'timezone' => [
                    'string',
                    'max:50',
                    'timezone:all',
                ],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [],
            ],
        ];
    }
}
