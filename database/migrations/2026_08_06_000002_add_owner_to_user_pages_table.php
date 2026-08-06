<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Polis\Contracts\Models\IsAnEntityContract;

/**
 * Generalises `user_pages` from the single-owner `user_id` FK to the polymorphic
 * owner_id/owner_type shape shared by Collection/Asset/Payment/Article, so a
 * page can be owned by ANY {@see IsAnEntityContract}
 * entity (a User today, an Organization next).
 *
 * Additive & non-destructive (see 2026_08_06_000001 for the rationale that
 * applies identically here):
 *   1. adds nullable owner_id / owner_type (guarded, so re-run is a no-op),
 *   2. backfills owner_type='user', owner_id=user_id for every existing row,
 *   3. RETAINS user_id (still the source of truth for the existing
 *      /users/{user}/pages surface; the model keeps a user() accessor),
 *   4. adds a composite (owner_type, owner_id) index.
 *
 * No FK constraint (users table is consumer-owned; ORM enforces the relation).
 */
return new class extends Migration
{
    private const INDEX_NAME = 'user_pages_owner_index';

    public function up(): void
    {
        if (! Schema::hasTable('user_pages')) {
            return;
        }

        Schema::table('user_pages', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_pages', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable();
            }
            if (! Schema::hasColumn('user_pages', 'owner_type')) {
                $table->string('owner_type')->nullable();
            }
        });

        if (Schema::hasColumn('user_pages', 'user_id')) {
            DB::table('user_pages')
                ->whereNotNull('user_id')
                ->where(function ($query): void {
                    $query->whereNull('owner_id')->orWhereNull('owner_type');
                })
                ->update([
                    'owner_id' => DB::raw('user_id'),
                    'owner_type' => 'user',
                ]);
        }

        if (! $this->indexExists(self::INDEX_NAME)) {
            Schema::table('user_pages', function (Blueprint $table): void {
                $table->index(['owner_type', 'owner_id'], self::INDEX_NAME);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_pages')) {
            return;
        }

        if ($this->indexExists(self::INDEX_NAME)) {
            Schema::table('user_pages', function (Blueprint $table): void {
                $table->dropIndex(self::INDEX_NAME);
            });
        }

        Schema::table('user_pages', function (Blueprint $table): void {
            if (Schema::hasColumn('user_pages', 'owner_type')) {
                $table->dropColumn('owner_type');
            }
            if (Schema::hasColumn('user_pages', 'owner_id')) {
                $table->dropColumn('owner_id');
            }
        });
    }

    private function indexExists(string $name): bool
    {
        $target = strtolower($name);

        foreach (Schema::getIndexes('user_pages') as $index) {
            if (strtolower((string) ($index['name'] ?? '')) === $target) {
                return true;
            }
        }

        return false;
    }
};
