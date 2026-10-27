<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persist which base statistics a DERIVED statistic depends on.
 *
 * A derived statistic (ratio/difference/sum over other statistics' cells) is
 * MATERIALIZED and recomputed eagerly whenever an operand it depends on is
 * (re)computed. To resolve "which derived statistics must I recompute now that
 * base statistic X changed?" cheaply, the operand relationship is persisted
 * here, indexed by `depends_on_statistic_id`.
 *
 * Columns:
 *   - statistic_id            : the derived statistic.
 *   - depends_on_statistic_id : a base (operand) statistic it reads.
 *   - position                : operand order (so the evaluator receives
 *                               numerator-before-denominator etc.).
 *
 * This lives in a separate table (and the whole derived layer is separable) so
 * it can be removed without touching the core engine. v1 forbids derived-on-
 * derived chains; see DerivedStatisticService.
 *
 * Package-owned + auto-loaded; Schema::hasTable guarded.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('statistic_dependencies')) {
            return;
        }

        Schema::create('statistic_dependencies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('statistic_id');
            $table->unsignedBigInteger('depends_on_statistic_id');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(
                ['statistic_id', 'depends_on_statistic_id'],
                'statistic_dependencies_unique',
            );
            $table->index('depends_on_statistic_id', 'statistic_dependencies_depends_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistic_dependencies');
    }
};
