<?php

declare(strict_types=1);

namespace Polis\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;

/**
 * @property int $id
 * @property int $user_page_id
 * @property string $component_type
 * @property int $display_order
 * @property array|null $config_json
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read UserPage $page
 */
class UserPageComponent extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasFactory, HasValidationRules, SoftDeletes;

    /**
     * @var array
     */
    protected $casts = [
        'display_order' => 'integer',
        'config_json' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(UserPage::class, 'user_page_id');
    }

    public function taskNodes(): HasMany
    {
        return $this->hasMany(\App\Models\User\TodoTaskNode::class, 'user_page_component_id');
    }

    public function rootTaskNodes(): HasMany
    {
        return $this->hasMany(\App\Models\User\TodoTaskNode::class, 'user_page_component_id')
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'component_type' => [
                    'string',
                    'in:'.implode(',', UserPage::VALID_COMPONENT_TYPES),
                ],
                'display_order' => [
                    'integer',
                    'min:0',
                ],
                'config_json' => [
                    'nullable',
                    'array',
                ],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => ['component_type'],
            ],
        ];
    }
}
