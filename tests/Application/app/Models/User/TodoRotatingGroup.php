<?php

declare(strict_types=1);

namespace App\Models\User;

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
 * @property int $todo_task_node_id
 * @property int $group_number
 * @property string|null $label
 * @property int $count_this_group
 * @property string $on_copy
 * @property string|null $last_date
 * @property bool $mark_done_on_group
 * @property int $cascade_ratio
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read TodoTaskNode $taskNode
 * @property-read \Illuminate\Database\Eloquent\Collection|TodoTaskNode[] $childNodes
 */
class TodoRotatingGroup extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasFactory, HasValidationRules, SoftDeletes;

    protected $casts = [
        'group_number' => 'integer',
        'count_this_group' => 'integer',
        'mark_done_on_group' => 'boolean',
        'cascade_ratio' => 'integer',
        'sort_order' => 'integer',
    ];

    public function taskNode(): BelongsTo
    {
        return $this->belongsTo(TodoTaskNode::class, 'todo_task_node_id');
    }

    public function childNodes(): HasMany
    {
        return $this->hasMany(TodoTaskNode::class, 'todo_rotating_group_id')->orderBy('sort_order');
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'group_number' => ['integer', 'min:0'],
                'label' => ['string', 'nullable', 'max:255'],
                'count_this_group' => ['integer'],
                'on_copy' => ['string', 'in:preserve,increment,reset'],
                'last_date' => ['string', 'nullable', 'max:30'],
                'mark_done_on_group' => ['boolean'],
                'cascade_ratio' => ['integer', 'min:1', 'max:10'],
                'sort_order' => ['integer', 'min:0'],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'group_number',
                ],
            ],
        ];
    }
}
