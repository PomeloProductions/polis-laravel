<?php

declare(strict_types=1);

namespace Polis\Models;

use App\Models\Wiki\Article;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\Traits\HasValidationRules;

/**
 * Class Category
 *
 * Tree-shaped via self-referential `parent_id`. Categories are global, so apps
 * can build shared taxonomies (e.g. Card Collecting's "Storage Tiers" root with
 * "Penny Sleeve / Hard Plastic / Magnetic" leaves).
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $description
 * @property string|null $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Category|null $parent
 * @property-read Collection<int,Category> $children
 * @property-read Collection<int, Article> $articles
 *
 * @method static \Database\Factories\CategoryFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Category query()
 *
 * @mixin \Eloquent
 */
class Category extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasValidationRules;

    /**
     * All articles associated with this category
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_category')
            ->withPivot('relevance')
            ->withTimestamps();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @param  mixed  ...$params
     */
    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'name' => [
                    'string',
                ],
                'description' => [
                    'nullable',
                    'string',
                ],
                'parent_id' => [
                    'nullable',
                    'integer',
                    'exists:categories,id',
                ],
                'color' => [
                    'nullable',
                    'string',
                    'max:16',
                ],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'name',
                ],
            ],
        ];
    }
}
