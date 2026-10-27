<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make `target_statistics` a multi-dimensional CELL store, and add the DB
 * uniqueness that fixes the dup-key-500 bug class.
 *
 * `target_statistics` keeps (target_type, target_id) as the PRIMARY axis and
 * `value`/`result` as before (back-compat). The EXTRA axes are captured by a new
 * `dimension_key` + the normalized child table `target_statistic_dimensions`
 * (approved "option A"): one cell per (statistic, target, dimension_key); each
 * extra axis value is one child row, so partial-tuple slice reads ("all
 * categories for this platform") and single-axis rollups are index-served
 * without a wide pivot.
 *
 * Changes:
 *   - target_statistics.dimension_key : string NOT NULL default '' — canonical
 *     hash of the sorted/canonicalized extra axes. '' == no extra axes, which is
 *     exactly the existing single-target cell, so old rows are valid unchanged.
 *   - UNIQUE (statistic_id, target_type, target_id, dimension_key) — the
 *     dup-500 fix; cells become firstOrCreate/updateOrCreate on this tuple.
 *   - new table target_statistic_dimensions: (target_statistic_id FK cascade,
 *     statistic_id denormalized, axis, value_type?, value_id?, value_scalar?)
 *     with (statistic_id, axis, value_id) and (statistic_id, axis, value_scalar)
 *     indexes so a slice by a partial tuple never needs a wide join.
 *
 * Package-owned + auto-loaded; Schema::hasColumn / hasTable guarded.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('target_statistics') && ! Schema::hasColumn('target_statistics', 'dimension_key')) {
            Schema::table('target_statistics', function (Blueprint $table): void {
                $table->string('dimension_key')->default('')->after('target_id');

                // The cell-uniqueness guarantee: one row per logical cell. This
                // is the dup-key-500 fix — uniqueness was app-enforced only.
                $table->unique(
                    ['statistic_id', 'target_type', 'target_id', 'dimension_key'],
                    'target_statistics_cell_unique',
                );
            });
        }

        if (! Schema::hasTable('target_statistic_dimensions')) {
            Schema::create('target_statistic_dimensions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('target_statistic_id');
                // Denormalized parent statistic id so partial-tuple slice queries
                // filter on (statistic_id, axis, value_*) without joining back to
                // target_statistics.
                $table->unsignedBigInteger('statistic_id');
                $table->string('axis');
                // When the axis value is an entity (a morph reference).
                $table->string('value_type')->nullable();
                $table->unsignedBigInteger('value_id')->nullable();
                // When the axis value is an enum/id/date-bucket scalar.
                $table->string('value_scalar')->nullable();
                $table->timestamps();

                $table->foreign('target_statistic_id')
                    ->references('id')
                    ->on('target_statistics')
                    ->cascadeOnDelete();

                $table->index(['statistic_id', 'axis', 'value_id'], 'tsd_stat_axis_value_id');
                $table->index(['statistic_id', 'axis', 'value_scalar'], 'tsd_stat_axis_value_scalar');
                $table->index('target_statistic_id', 'tsd_parent');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('target_statistic_dimensions');

        if (Schema::hasTable('target_statistics') && Schema::hasColumn('target_statistics', 'dimension_key')) {
            Schema::table('target_statistics', function (Blueprint $table): void {
                $table->dropUnique('target_statistics_cell_unique');
                $table->dropColumn('dimension_key');
            });
        }
    }
};
