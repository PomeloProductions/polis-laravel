<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the generic `pairwise_comparison_coverage` table.
 *
 * A reusable, domain-agnostic materialization of "how many times has each
 * unordered pair of NODES been compared, per owner, per coverage type". It is the
 * extractable core of HighScoresCenter's voting_pair_stats matrix (which counts
 * compared release/platform pairs per user per voting_type): every coverage /
 * missing / matrix read over a large comparison ledger reduces to this one count,
 * and materializing it turns an O(ledger) request-path scan into a tiny lookup.
 *
 * Terms are deliberately generic:
 *   - owner_id      : whose coverage this is (a user, an organization, ...).
 *   - coverage_type : the namespace of the comparison set (HSC passes its
 *                     voting_type here).
 *   - node_a_id / node_b_id : the two compared things, NORMALIZED so
 *                     node_a_id <= node_b_id (each unordered pair stored once; a
 *                     self-pair node_a_id == node_b_id is legal and represents
 *                     within-group comparisons, e.g. HSC's same-platform count).
 *   - compared_count: COUNT of comparison rows for the pair.
 *
 * Package-owned + auto-loaded (lives in database/package-migrations, registered by
 * BaseServiceProvider::loadMigrationsFrom) and guarded with Schema::hasTable so it
 * is safe for consumers that already own an equivalent table (HighScoresCenter
 * ships its own voting_pair_stats today and will migrate onto this once released).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pairwise_comparison_coverage')) {
            return;
        }

        Schema::create('pairwise_comparison_coverage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('coverage_type');
            $table->unsignedBigInteger('node_a_id');
            $table->unsignedBigInteger('node_b_id');
            $table->unsignedInteger('compared_count')->default(0);
            $table->timestamps();

            $table->unique(
                ['owner_id', 'coverage_type', 'node_a_id', 'node_b_id'],
                'pairwise_coverage_unique_pair',
            );
            $table->index(['owner_id', 'coverage_type'], 'pairwise_coverage_owner_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pairwise_comparison_coverage');
    }
};
