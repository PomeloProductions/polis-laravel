<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Polis\Contracts\Models\IsAnEntityContract;

/**
 * Generalises `article_notes` from the single-owner `user_id` FK to the
 * polymorphic owner_id/owner_type shape, so a note can be owned by ANY
 * {@see IsAnEntityContract} entity (a User today, an
 * Organization next).
 *
 * Additive & non-destructive (see 2026_08_06_000001):
 *   1. adds nullable owner_id / owner_type (guarded),
 *   2. backfills owner_type='user', owner_id=user_id,
 *   3. RETAINS user_id (source of truth for the /users/{user}/article-notes
 *      surface; the model keeps a user() accessor),
 *   4. adds a composite (owner_type, owner_id) index.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'article_notes_owner_index';

    public function up(): void
    {
        if (! Schema::hasTable('article_notes')) {
            return;
        }

        Schema::table('article_notes', function (Blueprint $table): void {
            if (! Schema::hasColumn('article_notes', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable();
            }
            if (! Schema::hasColumn('article_notes', 'owner_type')) {
                $table->string('owner_type')->nullable();
            }
        });

        if (Schema::hasColumn('article_notes', 'user_id')) {
            DB::table('article_notes')
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
            Schema::table('article_notes', function (Blueprint $table): void {
                $table->index(['owner_type', 'owner_id'], self::INDEX_NAME);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('article_notes')) {
            return;
        }

        if ($this->indexExists(self::INDEX_NAME)) {
            Schema::table('article_notes', function (Blueprint $table): void {
                $table->dropIndex(self::INDEX_NAME);
            });
        }

        Schema::table('article_notes', function (Blueprint $table): void {
            if (Schema::hasColumn('article_notes', 'owner_type')) {
                $table->dropColumn('owner_type');
            }
            if (Schema::hasColumn('article_notes', 'owner_id')) {
                $table->dropColumn('owner_id');
            }
        });
    }

    private function indexExists(string $name): bool
    {
        $target = strtolower($name);

        foreach (Schema::getIndexes('article_notes') as $index) {
            if (strtolower((string) ($index['name'] ?? '')) === $target) {
                return true;
            }
        }

        return false;
    }
};
