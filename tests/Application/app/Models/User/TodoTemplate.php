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
 * @property string $name
 * @property string $level
 * @property array $sections_json
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 */
class TodoTemplate extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasFactory, HasValidationRules, SoftDeletes;

    public const VALID_LEVELS = ['year', 'month', 'week', 'day'];

    protected $casts = [
        'sections_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'name' => [
                    'string',
                    'max:100',
                ],
                'level' => [
                    'string',
                    'in:'.implode(',', self::VALID_LEVELS),
                ],
                'sections_json' => [
                    'array',
                ],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => ['name', 'level', 'sections_json'],
            ],
        ];
    }
}
