<?php

declare(strict_types=1);

namespace Polis\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    /**
     * The node-tree model these component relations resolve to. Configured via
     * `polis.node_tree.node_model`. The package ships no default node model, so
     * a consumer that uses the node-tree relations must point this at their own
     * model (any {@see HasNodeTree} model) via config.
     *
     * @return class-string<Model>
     */
    public static function nodeModel(): string
    {
        $configured = function_exists('config')
            ? config('polis.node_tree.node_model')
            : null;

        if (is_string($configured) && class_exists($configured)) {
            return $configured;
        }

        throw new \RuntimeException(
            'No node-tree model configured. Set polis.node_tree.node_model to a '
            .'model class before using UserPageComponent node-tree relations.'
        );
    }

    protected static function nodeForeignKey(): string
    {
        $configured = function_exists('config')
            ? config('polis.node_tree.component_foreign_key')
            : null;

        return is_string($configured) && $configured !== '' ? $configured : 'user_page_component_id';
    }

    public function taskNodes(): HasMany
    {
        return $this->hasMany(static::nodeModel(), static::nodeForeignKey());
    }

    public function rootTaskNodes(): HasMany
    {
        return $this->hasMany(static::nodeModel(), static::nodeForeignKey())
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
