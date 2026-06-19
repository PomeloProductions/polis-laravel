<?php

declare(strict_types=1);

namespace Polis\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Polis\Models\Source;

/**
 * Lets any model carry external-source identifiers (PriceCharting product id,
 * IGDB game id, CardMarket product id, …) via the polymorphic `sources` table.
 *
 * A model can carry MULTIPLE foreign_ids for the same source — for example a
 * single TCGdex CardPrinting may be linked to several PriceCharting product
 * IDs when PC tracks finer variants than we model separately.
 *
 * Usage:
 *   class CardPrinting extends BaseModelAbstract
 *   {
 *       use HasExternalSources;
 *   }
 *
 *   $printing->sources;                                // all linked external ids
 *   $printing->getExternalId('price_charting');        // first id; null if none
 *   $printing->getExternalIds('price_charting');       // array<string>
 *   $printing->setExternalId('price_charting', '3457650');  // idempotent upsert
 *   $printing->forgetExternalId('price_charting', '3457650');  // drop a specific id
 *   $printing->forgetAllExternalIds('price_charting'); // drop every id for source
 *
 * Reverse lookup:
 *   $row = Source::where('source','price_charting')
 *                ->where('foreign_id','3457650')->first();
 *   $printing = $row?->item;
 */
trait HasExternalSources
{
    public function sources(): MorphMany
    {
        return $this->morphMany(Source::class, 'item');
    }

    public function getExternalId(string $source): ?string
    {
        $row = $this->sources()->where('source', $source)->first();

        return $row?->foreign_id;
    }

    /** @return array<int,string> */
    public function getExternalIds(string $source): array
    {
        return $this->sources()->where('source', $source)->pluck('foreign_id')->all();
    }

    public function setExternalId(string $source, string $foreignId, ?string $url = null): Source
    {
        /** @var Source $row */
        $row = $this->sources()->updateOrCreate(
            ['source' => $source, 'foreign_id' => $foreignId],
            ['url' => $url],
        );

        return $row;
    }

    /**
     * Source rows are pointers to external data, not auditable records. Soft-
     * deleting one leaves it occupying the (item_type, item_id, source,
     * foreign_id) unique slot — the next `setExternalId` for that same tuple
     * fails with a duplicate-key violation because `updateOrCreate`'s lookup
     * hides the soft-deleted row but MySQL's unique index doesn't.
     * `forceDelete()` removes the row entirely, freeing the slot.
     */
    public function forgetExternalId(string $source, string $foreignId): void
    {
        $this->sources()->where('source', $source)->where('foreign_id', $foreignId)->forceDelete();
    }

    public function forgetAllExternalIds(string $source): void
    {
        $this->sources()->where('source', $source)->forceDelete();
    }
}
