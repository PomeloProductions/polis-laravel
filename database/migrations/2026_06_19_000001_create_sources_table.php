<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Polis\Models\Source;
use Polis\Traits\HasExternalSources;

/**
 * Create the `sources` table that backs {@see Source} and
 * {@see HasExternalSources}.
 *
 * Schema rationale:
 *
 *  - Polymorphic by (`item_type`, `item_id`). Any model that uses the
 *    HasExternalSources trait can attach external identifiers without
 *    needing its own join table.
 *
 *  - One model can hold MULTIPLE `foreign_id`s for the same `source`,
 *    so the natural-key unique constraint includes `foreign_id` rather
 *    than just (`item_type`, `item_id`, `source`). That mirrors the
 *    upstream Card Collecting use case where a single TCGdex
 *    CardPrinting maps to several PriceCharting product IDs.
 *
 *  - Reverse-lookup index on (`source`, `foreign_id`) so the common
 *    "given this external id, which local model owns it?" path stays
 *    fast on large tables.
 *
 *  - Standard morph index on (`item_type`, `item_id`) for the forward
 *    relationship loaded by the trait's `sources()` morphMany.
 *
 *  - Soft-deletes are present on the table because BaseModelAbstract uses
 *    the SoftDeletes trait; however the HasExternalSources trait's
 *    `forgetExternalId` / `forgetAllExternalIds` deliberately call
 *    `forceDelete()` to release the unique slot. Soft-delete is therefore
 *    available for callers that want to retain history but is NOT the
 *    default forget path — see the trait's docblock for the rationale.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sources')) {
            return;
        }

        Schema::create('sources', function (Blueprint $table): void {
            $table->id();

            // Polymorphic owner pointer. Standard morphs() would name the
            // columns `itemable_type` / `itemable_id`; we want plain
            // `item_type` / `item_id` to match the trait's morphMany
            // declaration (`morphMany(Source::class, 'item')`). Defining
            // the columns + index manually keeps the names aligned.
            $table->string('item_type');
            $table->unsignedBigInteger('item_id');

            // The external system identifier (e.g. 'price_charting',
            // 'igdb', 'card_market').
            $table->string('source');

            // The identifier inside that external system.
            $table->string('foreign_id');

            // Optional canonical URL for the external record.
            $table->string('url')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Forward lookup: load all external ids for a given owner.
            $table->index(['item_type', 'item_id'], 'sources_item_morph_index');

            // Reverse lookup: find the owner given an external id.
            $table->index(['source', 'foreign_id'], 'sources_source_foreign_id_index');

            // Natural-key uniqueness for the trait's updateOrCreate upsert.
            // Includes `foreign_id` (not just source) because a single
            // model can attach multiple foreign ids for the same source.
            $table->unique(
                ['item_type', 'item_id', 'source', 'foreign_id'],
                'sources_item_source_foreign_id_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
