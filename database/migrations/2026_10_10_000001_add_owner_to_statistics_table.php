<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Polis\Contracts\Models\IsAnEntityContract;

/**
 * Adds the polymorphic owner_id/owner_type shape to `statistics`, so a
 * statistic can optionally belong to an owning {@see IsAnEntityContract}
 * entity (a User today, an Organization next) instead of only being a
 * global/public definition.
 *
 * Purely additive & non-destructive — unlike the articles / article_notes
 * owner migrations there is NO pre-existing single-owner FK on `statistics`
 * to convert or backfill, so this migration only:
 *   1. adds nullable owner_id (unsigned big int) + owner_type (string),
 *   2. adds a composite (owner_type, owner_id) index.
 *
 * A NULL owner means a GLOBAL/public statistic — the current, pre-owner
 * behaviour — so every existing row stays exactly as it is and the change is
 * fully backward-compatible. Owned (per-user / per-org) statistics are opt-in
 * by setting the two columns; this enables the "year statistics" series'
 * per-user statistics without disturbing the shared global definitions.
 *
 * Idempotent (each step guards with Schema::hasColumn / index existence) so a
 * partial or retried migrate cannot throw.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'statistics_owner_index';

    public function up(): void
    {
        if (! Schema::hasTable('statistics')) {
            return;
        }

        Schema::table('statistics', function (Blueprint $table): void {
            if (! Schema::hasColumn('statistics', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable();
            }
            if (! Schema::hasColumn('statistics', 'owner_type')) {
                $table->string('owner_type')->nullable();
            }
        });

        if (! $this->indexExists(self::INDEX_NAME)) {
            Schema::table('statistics', function (Blueprint $table): void {
                $table->index(['owner_type', 'owner_id'], self::INDEX_NAME);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('statistics')) {
            return;
        }

        if ($this->indexExists(self::INDEX_NAME)) {
            Schema::table('statistics', function (Blueprint $table): void {
                $table->dropIndex(self::INDEX_NAME);
            });
        }

        Schema::table('statistics', function (Blueprint $table): void {
            if (Schema::hasColumn('statistics', 'owner_type')) {
                $table->dropColumn('owner_type');
            }
            if (Schema::hasColumn('statistics', 'owner_id')) {
                $table->dropColumn('owner_id');
            }
        });
    }

    private function indexExists(string $name): bool
    {
        $target = strtolower($name);

        foreach (Schema::getIndexes('statistics') as $index) {
            if (strtolower((string) ($index['name'] ?? '')) === $target) {
                return true;
            }
        }

        return false;
    }
};
