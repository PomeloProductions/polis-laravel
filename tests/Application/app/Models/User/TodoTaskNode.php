<?php

declare(strict_types=1);

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;
use Polis\Models\User\UserPageComponent;

/**
 * @property int $id
 * @property int $user_page_component_id
 * @property int|null $parent_id
 * @property int $sort_order
 * @property string $client_id
 * @property string $task_type
 * @property string $label
 * @property string|null $description
 * @property bool $collapsed
 * @property float|null $tally
 * @property float $tally_step
 * @property array|null $schedule
 * @property string $on_copy
 * @property float|null $time_budget_hours
 * @property float $logged_hours
 * @property float $logged_time
 * @property float $deficit
 * @property string $tracking_mode
 * @property bool $decrement_on_done
 * @property string $time_tracking_mode
 * @property int|null $todo_balance_id
 * @property int|null $todo_rotating_group_id
 * @property bool $completed
 * @property string|null $last_date
 * @property bool $custom_groups
 * @property int $cascade_ratio
 * @property int|null $count_this_group
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read UserPageComponent $component
 * @property-read TodoTaskNode|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|TodoTaskNode[] $children
 * @property-read \Illuminate\Database\Eloquent\Collection|TodoRotatingGroup[] $groups
 * @property-read \Illuminate\Database\Eloquent\Collection|TodoSubItem[] $subItems
 * @property-read TodoBalance|null $todoBalance
 * @property-read TodoRotatingGroup|null $rotatingGroup
 */
class TodoTaskNode extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasFactory, HasValidationRules, SoftDeletes;

    public const TASK_TYPE_CATEGORY = 'category';
    public const TASK_TYPE_ROTATING = 'rotating';
    public const TASK_TYPE_LINE_ITEM = 'line_item';
    /** A rotation slot under a rotating node — an ordinary child node that groups items. */
    public const TASK_TYPE_PRIORITY_GROUP = 'priority_group';

    protected $casts = [
        'sort_order' => 'integer',
        'collapsed' => 'boolean',
        'tally' => 'decimal:2',
        'tally_step' => 'decimal:2',
        'schedule' => 'array',
        'time_budget_hours' => 'decimal:2',
        'logged_hours' => 'decimal:4',
        'logged_time' => 'decimal:4',
        'deficit' => 'decimal:4',
        'decrement_on_done' => 'boolean',
        'show_checkmark' => 'boolean',
        'completed' => 'boolean',
        'custom_groups' => 'boolean',
        'cascade_ratio' => 'integer',
        'count_this_group' => 'integer',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(UserPageComponent::class, 'user_page_component_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(TodoRotatingGroup::class)->orderBy('sort_order');
    }

    public function subItems(): HasMany
    {
        return $this->hasMany(TodoSubItem::class)->orderBy('sort_order');
    }

    public function todoBalance(): BelongsTo
    {
        return $this->belongsTo(TodoBalance::class);
    }

    public function rotatingGroup(): BelongsTo
    {
        return $this->belongsTo(TodoRotatingGroup::class, 'todo_rotating_group_id');
    }

    public function calendars(): BelongsToMany
    {
        return $this->belongsToMany(TodoCalendar::class, 'todo_node_calendars')
            ->withPivot('mode', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'client_id' => ['string', 'max:50'],
                'task_type' => ['string', 'in:category,rotating,line_item,priority_group'],
                'label' => ['string', 'max:255'],
                'description' => ['string', 'nullable'],
                'collapsed' => ['boolean'],
                'tally' => ['numeric', 'nullable'],
                'tally_step' => ['numeric'],
                'schedule' => ['array', 'nullable'],
                'on_copy' => ['string', 'in:increment,preserve,reset,omit'],
                'time_budget_hours' => ['numeric', 'nullable'],
                'logged_hours' => ['numeric'],
                'logged_time' => ['numeric'],
                'deficit' => ['numeric'],
                'tracking_mode' => ['string', 'in:units,hours'],
                'decrement_on_done' => ['boolean'],
                'time_tracking_mode' => ['string', 'in:reset,accumulative'],
                'completed' => ['boolean'],
                'last_date' => ['string', 'nullable', 'max:10'],
                'custom_groups' => ['boolean'],
                'cascade_ratio' => ['integer', 'min:1', 'max:10'],
                'count_this_group' => ['integer', 'nullable'],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'client_id',
                    'task_type',
                ],
            ],
        ];
    }
}
