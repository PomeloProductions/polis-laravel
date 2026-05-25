<?php

declare(strict_types=1);

namespace Polis\Models\Collection;

use App\Models\Category;
use App\Models\Collection\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Polis\Contracts\Models\CanBeAggregatedContract;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;

/**
 * App\Models\Collection\CollectionItem
 *
 * @property int $id
 * @property int $item_id
 * @property string $item_type
 * @property int $collection_id
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $categories
 * @property-read int|null $categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CollectionItemMeta> $meta
 * @property-read Collection $collection
 * @property-read Model|\Eloquent $item
 *
 * @mixin \Eloquent
 */
class CollectionItem extends BaseModelAbstract implements CanBeAggregatedContract, HasValidationRulesContract
{
    use HasValidationRules;

    /**
     * The item this is related to
     */
    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * All categories for this collection item
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'collection_item_categories');
    }

    /**
     * Generic key/value metadata bag — apps use this for arbitrary per-item
     * fields without growing the collection_items schema.
     */
    public function meta(): HasMany
    {
        return $this->hasMany(CollectionItemMeta::class);
    }

    /**
     * The collection this item is apart of
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Convenience accessor — returns the meta_value for a key, or null.
     */
    public function getMeta(string $key): ?string
    {
        $row = $this->meta()->where('meta_key', $key)->first();
        return $row?->meta_value;
    }

    /**
     * Convenience mutator — upserts a meta row. Passing null deletes the row.
     */
    public function setMeta(string $key, ?string $value): void
    {
        if ($value === null) {
            $this->meta()->where('meta_key', $key)->delete();
            return;
        }
        $this->meta()->updateOrCreate(
            ['meta_key' => $key],
            ['meta_value' => $value],
        );
    }

    public function buildModelValidationRules(...$params): array
    {
        return [
            self::VALIDATION_RULES_BASE => [
                'item_id' => [
                    'integer',
                ],
                'item_type' => [
                    Rule::in(['article']),
                ],
                'order' => [
                    'integer',
                ],
            ],
            self::VALIDATION_RULES_CREATE => [
                self::VALIDATION_PREPEND_REQUIRED => ['item_id', 'item_type', 'order'],
            ],
        ];
    }

    /**
     * Returns the relation paths to the models that can be target statistics
     * For example: ["collection"] would mean this model affects statistics on collections
     * through the collection relation
     *
     * @return string[]
     */
    public function getStatisticTargetRelationPath(): array
    {
        return ['collection'];
    }
}
