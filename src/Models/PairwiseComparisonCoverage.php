<?php

declare(strict_types=1);

namespace Polis\Models;

/**
 * Class PairwiseComparisonCoverage
 *
 * One row per (owner, coverage_type, normalized node pair) holding the count of
 * comparisons between the two nodes. The reusable core of a "pairwise comparison
 * coverage matrix": a materialized numerator for every coverage / missing /
 * matrix read over a large comparison ledger, so the request path reads a tiny
 * matrix instead of scanning the ledger.
 *
 * The pair is normalized so node_a_id <= node_b_id; a self-pair (a == b) is legal
 * and represents within-group comparisons. See the
 * create_pairwise_comparison_coverage_table package migration and
 * {@see \Polis\Services\Statistic\MaintainsPairwiseCoverage} /
 * {@see \Polis\Services\Statistic\PairwiseCoverageMatrix}.
 *
 * This is a pure COUNTER table, not an audited domain record, so it deliberately
 * opts OUT of the soft deletes that {@see BaseModelAbstract} applies to every other
 * model: {@see \Polis\Services\Statistic\MaintainsPairwiseCoverage::decrementCoverage()}
 * hard-deletes a pair the moment its count hits zero so the matrix never carries
 * phantom zero rows, and a later increment re-creates it fresh via an upsert on the
 * unique pair key. A soft delete would leave a trashed row behind that the upsert
 * would collide with, and would make every read append a `deleted_at` filter against
 * a column the migration intentionally never creates. The table therefore has no
 * `deleted_at` column; see the overrides below.
 *
 * @property int $id
 * @property int $owner_id
 * @property string $coverage_type
 * @property int $node_a_id
 * @property int $node_b_id
 * @property int $compared_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PairwiseComparisonCoverage extends BaseModelAbstract
{
    protected $table = 'pairwise_comparison_coverage';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'owner_id' => 'integer',
        'node_a_id' => 'integer',
        'node_b_id' => 'integer',
        'compared_count' => 'integer',
    ];

    /**
     * This table has no `deleted_at` column, so override the base `$hidden`
     * (which hides `deleted_at`) to drop that reference.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Opt this pure counter table out of soft deletes (see class docblock).
     *
     * Overriding the trait's boot hook to a no-op keeps the SoftDeletingScope
     * from being registered, so reads never append `where deleted_at is null`
     * against a column that does not exist.
     */
    public static function bootSoftDeletes(): void
    {
        // Intentionally empty: no soft-delete global scope for this model.
    }

    /**
     * Make `delete()` a real hard delete so decrement-at-zero actually removes
     * the row (and a later increment can re-create it via upsert).
     */
    protected function performDeleteOnModel(): void
    {
        $this->setKeysForSaveQuery($this->newModelQuery())->forceDelete();

        $this->exists = false;
    }
}
