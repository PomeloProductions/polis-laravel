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
 * @property int $todo_task_node_id
 * @property string $client_id
 * @property string $text
 * @property bool $completed
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read TodoTaskNode $taskNode
 */
class TodoSubItem extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasFactory, HasValidationRules, SoftDeletes;

    protected $casts = [
        'completed' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function taskNode(): BelongsTo
    {
        return $this->belongsTo(TodoTaskNode::class, 'todo_task_node_id');
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'client_id' => ['string', 'max:50'],
                'text' => ['string', 'max:500'],
                'completed' => ['boolean'],
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
