<?php

declare(strict_types=1);

namespace Polis\Services\Statistic;

use Polis\Models\PairwiseComparisonCoverage;

/**
 * Reusable read helper for a pairwise comparison coverage matrix
 * ({@see PairwiseComparisonCoverage}).
 *
 * Returns an (owner, coverage_type) slice as a node x node count matrix and
 * provides the in-memory sums that coverage/missing reads need, replacing a scan
 * of the underlying comparison ledger. It is DOMAIN-agnostic: it works purely in
 * node ids. Consumers map their domain groups to node-id sets before calling (e.g.
 * HighScoresCenter expands a platform group to its platform ids and passes those),
 * and apply any read-time grouping themselves.
 *
 * Self-heal is intentionally NOT baked in here, because deciding whether an empty
 * slice is "unbuilt" vs "legitimately empty" requires knowledge of the consumer's
 * ledger. A consumer wires self-heal by checking its own ledger and calling
 * {@see MaintainsPairwiseCoverage::replaceCoverageSlice()} on a miss, then re-reading.
 */
class PairwiseCoverageMatrix
{
    /**
     * The count matrix for an (owner, coverage_type) as
     * node_a_id => node_b_id => count, normalized node_a_id <= node_b_id (self
     * counts on the diagonal).
     *
     * @return array<int, array<int, int>>
     */
    public function forOwner(int $ownerId, string $coverageType): array
    {
        $matrix = [];
        $cursor = PairwiseComparisonCoverage::query()
            ->where('owner_id', $ownerId)
            ->where('coverage_type', $coverageType)
            ->select('node_a_id', 'node_b_id', 'compared_count')
            ->cursor();

        /** @var PairwiseComparisonCoverage $row */
        foreach ($cursor as $row) {
            $matrix[(int) $row->node_a_id][(int) $row->node_b_id] = (int) $row->compared_count;
        }

        return $matrix;
    }

    /**
     * Sum counts for every pair with BOTH nodes in $nodeIds (includes the
     * self/diagonal pairs). Mirrors a "compared within this group" numerator.
     *
     * @param array<int, array<int, int>> $matrix
     * @param int[] $nodeIds
     */
    public function sumWithin(array $matrix, array $nodeIds): int
    {
        $inSet = array_fill_keys($nodeIds, true);
        $total = 0;
        foreach ($matrix as $nodeA => $byB) {
            if (! isset($inSet[$nodeA])) {
                continue;
            }
            foreach ($byB as $nodeB => $count) {
                if (isset($inSet[$nodeB])) {
                    $total += $count;
                }
            }
        }

        return $total;
    }

    /**
     * Sum counts for every pair with one node in $aNodeIds and the other in
     * $bNodeIds (either orientation). Mirrors a "compared across two groups"
     * numerator.
     *
     * @param array<int, array<int, int>> $matrix
     * @param int[] $aNodeIds
     * @param int[] $bNodeIds
     */
    public function sumAcross(array $matrix, array $aNodeIds, array $bNodeIds): int
    {
        $inA = array_fill_keys($aNodeIds, true);
        $inB = array_fill_keys($bNodeIds, true);
        $total = 0;
        foreach ($matrix as $nodeA => $byB) {
            foreach ($byB as $nodeB => $count) {
                if ((isset($inA[$nodeA]) && isset($inB[$nodeB]))
                    || (isset($inA[$nodeB]) && isset($inB[$nodeA]))) {
                    $total += $count;
                }
            }
        }

        return $total;
    }
}
