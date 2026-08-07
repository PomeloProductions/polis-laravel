<?php

declare(strict_types=1);

namespace Polis\Models\User;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;
use Polis\Models\Traits\IsOwnedByEntity;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $owner_id
 * @property string|null $owner_type
 * @property string $slug
 * @property string $name
 * @property string $icon
 * @property string|null $color
 * @property string $route_path
 * @property string $page_type
 * @property int $display_order
 * @property bool $is_visible
 * @property bool $is_required
 * @property bool $is_nav_item
 * @property int|null $parent_page_id
 * @property array|null $config_json
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 * @property-read Model|\Eloquent|null $owner
 * @property-read UserPage|null $parentPage
 * @property-read Collection<int, UserPage> $childPages
 * @property-read Collection<int, UserPageComponent> $components
 */
class UserPage extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasFactory, HasValidationRules, IsOwnedByEntity, SoftDeletes;

    public const VALID_PAGE_TYPES = ['dashboard', 'list', 'detail', 'todo'];

    public const VALID_COMPONENT_TYPES = [
        'day_summary',
        'stats_cards',
        'page_manager',
        'settings_panel',
        'todo_task',
        'todo_bullet_list',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'display_order' => 'integer',
        'is_visible' => 'boolean',
        'is_required' => 'boolean',
        'is_nav_item' => 'boolean',
        'config_json' => 'array',
    ];

    /**
     * The user who owns this page.
     *
     * Retained for backward-compat; the canonical owner is now the polymorphic
     * owner() relation (via {@see IsOwnedByEntity}), which resolves to the same
     * user for existing rows and can point at any entity type for new ones.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parentPage(): BelongsTo
    {
        return $this->belongsTo(UserPage::class, 'parent_page_id');
    }

    public function childPages(): HasMany
    {
        return $this->hasMany(UserPage::class, 'parent_page_id')->orderBy('display_order');
    }

    public function components(): HasMany
    {
        return $this->hasMany(UserPageComponent::class)->orderBy('display_order');
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'slug' => [
                    'string',
                    'max:50',
                    'regex:/^[a-z0-9][a-z0-9_\/-]*$/',
                ],
                'name' => [
                    'string',
                    'max:100',
                ],
                'icon' => [
                    'string',
                    'max:50',
                ],
                'color' => [
                    'nullable',
                    'string',
                    'max:7',
                    'regex:/^#[0-9A-Fa-f]{6}$/',
                ],
                'route_path' => [
                    'string',
                    'max:100',
                ],
                'page_type' => [
                    'string',
                    'in:'.implode(',', self::VALID_PAGE_TYPES),
                ],
                'display_order' => [
                    'integer',
                    'min:0',
                ],
                'is_visible' => [
                    'boolean',
                ],
                'is_nav_item' => [
                    'boolean',
                ],
                'parent_page_id' => [
                    'nullable',
                    'integer',
                ],
                'config_json' => [
                    'nullable',
                    'array',
                ],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => ['name', 'route_path', 'page_type'],
            ],
        ];
    }
}
