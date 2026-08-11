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
 * @property int $todo_rotating_group_id
 * @property string $client_id
 * @property string $text
 * @property string|null $last_date
 * @property string|null $on_copy
 * @property int $count
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read TodoRotatingGroup $group
 */
class TodoRotatingItem extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasFactory, HasValidationRules, SoftDeletes;

    protected $casts = [
        'count' => 'integer',
        'sort_order' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(TodoRotatingGroup::class, 'todo_rotating_group_id');
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'client_id' => ['string', 'max:50'],
                'text' => ['string', 'max:500'],
                'last_date' => ['string', 'nullable', 'max:10'],
                'on_copy' => ['string', 'nullable', 'in:preserve,increment,reset'],
                'count' => ['integer'],
                'sort_order' => ['integer', 'min:0'],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'client_id',
                    'text',
                ],
            ],
        ];
    }
}
