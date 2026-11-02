<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a declarative `joins` list to `statistics` (additive, backward-compatible).
 *
 * The multi-dimensional GROUP BY engine previously queried ONE base table with no
 * joins, which forced consumers (HSC) to build bespoke per-shape SQL VIEWS so
 * their axes/filters could reach related tables. This column lets a statistic
 * COUNT one base table while its axes/filters/measure/primary target reference
 * RELATED tables — no views.
 *
 * New column:
 *   - joins : json — an ordered list of declarative joins. Each entry:
 *       { table, alias, left: "base.id", right: "alias.col",
 *         type: inner|left (default inner),
 *         guard_soft_deletes: bool (default true),
 *         on_extra: [ [col, op, value], ... ] }
 *     Joins apply in order so a later join may reference an earlier alias. The
 *     soft-delete guard excludes deleted_at rows (INNER → WHERE, LEFT → ON) when
 *     the joined table has the column.
 *
 * A statistic that declares no `joins` (NULL) behaves exactly as before, so this
 * is fully back-compatible with every existing engine-backed statistic.
 *
 * Package-owned + auto-loaded (database/package-migrations, registered by
 * BaseServiceProvider::boot()). Guarded with Schema::hasColumn so it is safe on
 * consumers that have already run it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('statistics')) {
            return;
        }

        Schema::table('statistics', function (Blueprint $table): void {
            if (! Schema::hasColumn('statistics', 'joins')) {
                $table->json('joins')->nullable()->after('dimensions');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('statistics')) {
            return;
        }

        Schema::table('statistics', function (Blueprint $table): void {
            if (Schema::hasColumn('statistics', 'joins')) {
                $table->dropColumn('joins');
            }
        });
    }
};
