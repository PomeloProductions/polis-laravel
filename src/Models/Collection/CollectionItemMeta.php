<?php

declare(strict_types=1);

namespace Polis\Models\Collection;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Polis\Models\BaseModelAbstract;

/**
 * Generic key/value metadata for a CollectionItem. Apps attach arbitrary
 * per-item fields here (e.g. condition, acquired_price, notes) without
 * growing the collection_items schema itself.
 *
 * @property int $id
 * @property int $collection_item_id
 * @property string $meta_key
 * @property string|null $meta_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CollectionItem $collectionItem
 */
class CollectionItemMeta extends BaseModelAbstract
{
    protected $table = 'collection_item_meta';

    public function collectionItem(): BelongsTo
    {
        return $this->belongsTo(CollectionItem::class);
    }
}
