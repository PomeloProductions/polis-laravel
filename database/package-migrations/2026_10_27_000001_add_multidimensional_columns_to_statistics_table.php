<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make `statistics` multi-dimensional-aware (additive, backward-compatible).
 *
 * Existing single-target statistics have NULL in every new column and keep
 * running through the unchanged in-PHP relation-traversal path. A statistic
 * opts into the new GROUP BY engine by declaring `dimensions` + `measure`; a
 * `kind = 'derived'` statistic is an expression over other statistics' cells.
 *
 * New columns:
 *   - kind              : 'count' (default/implicit for existing rows) or
 *                         'derived'. First-class so derived statistics are a
 *                         declared type, not a convention.
 *   - dimensions        : json — the ordered EXTRA axes (name + how to resolve
 *                         each axis value: a column or relation, value kind
 *                         entity|scalar|date_bucket, optional pair_group for an
 *                         unordered self-cross pair, and date_bucket
 *                         granularity/timezone/week_start).
 *   - measure           : json — the scalar aggregate ({agg: count|
 *                         count_distinct|sum|max, column?}). count_distinct
 *                         carries the count-once grain so fan-out joins don't
 *                         inflate.
 *   - derived_definition: json — for a derived statistic: {operation: ratio|
 *                         difference|sum, operands: [statistic_id, ...]}.
 *
 * Package-owned + auto-loaded (database/package-migrations, registered by
 * BaseServiceProvider::boot()). Guarded with Schema::hasColumn so it is safe on
 * consumers that have already run it, and the whole module is additive so no
 * existing statistic behavior changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('statistics')) {
            return;
        }

        Schema::table('statistics', function (Blueprint $table): void {
            if (! Schema::hasColumn('statistics', 'kind')) {
                $table->string('kind')->default('count')->after('name');
            }

            if (! Schema::hasColumn('statistics', 'dimensions')) {
                $table->json('dimensions')->nullable()->after('relation');
            }

            if (! Schema::hasColumn('statistics', 'measure')) {
                $table->json('measure')->nullable()->after('dimensions');
            }

            if (! Schema::hasColumn('statistics', 'derived_definition')) {
                $table->json('derived_definition')->nullable()->after('measure');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('statistics')) {
            return;
        }

        Schema::table('statistics', function (Blueprint $table): void {
            foreach (['kind', 'dimensions', 'measure', 'derived_definition'] as $column) {
                if (Schema::hasColumn('statistics', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
