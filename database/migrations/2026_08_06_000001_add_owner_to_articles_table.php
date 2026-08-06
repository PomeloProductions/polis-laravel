<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Polis\Contracts\Models\IsAnEntityContract;

/**
 * Converts `articles` from the single-owner `organization_id` FK to a true
 * polymorphic owner (`owner_id` / `owner_type`) — the same shape Collection,
 * Asset, Payment, PaymentMethod and Subscription already use — so an Article
 * ("contract") can be owned by ANY {@see IsAnEntityContract}
 * entity (an Organization today, a User next, any future entity type).
 *
 * SAFEST NON-DESTRUCTIVE PATH
 * ---------------------------
 * This migration is deliberately ADDITIVE and NON-DESTRUCTIVE:
 *
 *   1. It ADDS the nullable `owner_id` / `owner_type` columns (guarded on
 *      Schema::hasColumn so a re-run is a no-op).
 *   2. It BACKFILLS every existing row that has a non-null `organization_id`:
 *        owner_type = 'organization'  (the morph alias — see the morph map in
 *                                      BaseRepositoryProvider),
 *        owner_id   = organization_id.
 *      Rows with a null `organization_id` (platform-wide wiki articles / global
 *      templates) are left with a null owner, exactly as before.
 *   3. It KEEPS `organization_id` in place (still nullable). It is NOT dropped.
 *      Dropping it would be a destructive, irreversible change and would break:
 *        - the composite (key, organization_id) unique index used by the
 *          EmailTemplate/PushTemplate multi-tenant override feature,
 *        - the standalone articles_organization_id_index,
 *        - Article's backward-compat organization() accessor and any consumer
 *          still reading organization_id.
 *      Keeping it means both columns stay in sync going forward for
 *      Organization-owned articles (the controller stamps both), and the column
 *      remains available for the template-override feature. A future, separate,
 *      opt-in migration can drop it once every consumer has cut over.
 *
 *   4. It adds a composite `articles_owner_index` on (owner_type, owner_id) so
 *      the entity-scoped listing can filter on the polymorphic owner
 *      efficiently — the leading-column analogue of the existing
 *      articles_organization_id_index.
 *
 * No foreign-key constraint is declared (matching the 2026_05_30 and 2026_08_04
 * migrations): the owning tables (organizations / users) are owned by the
 * consuming app and may not exist at migrate time on every consumer. The
 * association is enforced at the ORM layer by Article's owner() morph relation.
 *
 * down() drops the columns + index and is safe: the retained organization_id is
 * the source of truth for Organization-owned articles, so no data is lost.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'articles_owner_index';

    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            if (! Schema::hasColumn('articles', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable();
            }
            if (! Schema::hasColumn('articles', 'owner_type')) {
                $table->string('owner_type')->nullable();
            }
        });

        // Backfill the polymorphic owner from the legacy organization_id FK.
        if (Schema::hasColumn('articles', 'organization_id')) {
            DB::table('articles')
                ->whereNotNull('organization_id')
                ->where(function ($query): void {
                    $query->whereNull('owner_id')->orWhereNull('owner_type');
                })
                ->update([
                    'owner_id' => DB::raw('organization_id'),
                    'owner_type' => 'organization',
                ]);
        }

        if (! $this->indexExists(self::INDEX_NAME)) {
            Schema::table('articles', function (Blueprint $table): void {
                $table->index(['owner_type', 'owner_id'], self::INDEX_NAME);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists(self::INDEX_NAME)) {
            Schema::table('articles', function (Blueprint $table): void {
                $table->dropIndex(self::INDEX_NAME);
            });
        }

        Schema::table('articles', function (Blueprint $table): void {
            if (Schema::hasColumn('articles', 'owner_type')) {
                $table->dropColumn('owner_type');
            }
            if (Schema::hasColumn('articles', 'owner_id')) {
                $table->dropColumn('owner_id');
            }
        });
    }

    /**
     * Driver-agnostic check for whether an index already exists on `articles`.
     * Uses the Schema builder's introspection (Laravel 11+) so we don't depend
     * on doctrine/dbal, which is not installed in this package.
     */
    private function indexExists(string $name): bool
    {
        $target = strtolower($name);

        foreach (Schema::getIndexes('articles') as $index) {
            if (strtolower((string) ($index['name'] ?? '')) === $target) {
                return true;
            }
        }

        return false;
    }
};
