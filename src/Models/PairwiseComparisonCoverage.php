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
}
