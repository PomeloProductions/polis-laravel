<?php

declare(strict_types=1);

namespace Polis\Services\Statistic;

use Illuminate\Support\Facades\DB;
use Polis\Models\PairwiseComparisonCoverage;

/**
 * Reusable incremental maintenance for a pairwise comparison coverage matrix
 * ({@see PairwiseComparisonCoverage}).
 *
 * Call {@see increment()} / {@see decrement()} with the two NODE ids each time a
 * comparison is added to / removed from the underlying ledger (O(1) per call),
 * {@see clearSlice()} when a whole (owner, coverage_type) ledger is wiped, and
 * {@see reconcileFromRows()} / {@see replaceSlice()} for a full rebuild from an
 * authoritative source (backfill, drift-repair, read-path self-heal).
 *
 * This service deliberately knows nothing about the DOMAIN of a node. Consumers
 * map their domain objects to node ids before calling: HighScoresCenter resolves
 * each release to its platform_id and passes platform ids as nodes, keyed by its
 * voting_type as the coverage_type and user id as the owner. Because node-id
 * resolution (and any grouping applied at read time) is domain-specific, it stays
 * in the consumer; only the generic count bookkeeping lives here.
 *
 * Trait, not inheritance: expose it however suits the consumer (inject the class,
 * or `use` it on a domain service). It holds no state, so it is safe to reuse.
 */
trait MaintainsPairwiseCoverage
{
    /**
     * Record that NODE a and NODE b were compared: +1 to the normalized pair
     * (creating the row at 1 on first sight).
     */
    public function incrementCoverage(int $ownerId, string $coverageType, int $nodeAId, int $nodeBId): void
    {
        [$a, $b] = $this->normalizePair($nodeAId, $nodeBId);

        DB::table('pairwise_comparison_coverage')->upsert(
            [[
                'owner_id' => $ownerId,
                'coverage_type' => $coverageType,
                'node_a_id' => $a,
                'node_b_id' => $b,
                'compared_count' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['owner_id', 'coverage_type', 'node_a_id', 'node_b_id'],
            ['compared_count' => DB::raw('pairwise_comparison_coverage.compared_count + 1'), 'updated_at' => now()],
        );
    }

    /**
     * Record that a comparison between NODE a and NODE b was removed: -1, deleting
     * the row at zero so the matrix never holds phantom zero rows.
     */
    public function decrementCoverage(int $ownerId, string $coverageType, int $nodeAId, int $nodeBId): void
    {
        [$a, $b] = $this->normalizePair($nodeAId, $nodeBId);

        /** @var PairwiseComparisonCoverage|null $row */
        $row = PairwiseComparisonCoverage::query()
            ->where('owner_id', $ownerId)
            ->where('coverage_type', $coverageType)
            ->where('node_a_id', $a)
            ->where('node_b_id', $b)
            ->first();

        if ($row === null) {
            return;
        }

        if ($row->compared_count <= 1) {
            $row->delete();

            return;
        }

        $row->compared_count -= 1;
        $row->save();
    }

    /**
     * Drop the entire matrix slice for an (owner, coverage_type) — use when the
     * underlying ledger for the slice is wiped wholesale.
     */
    public function clearCoverageSlice(int $ownerId, string $coverageType): void
    {
        PairwiseComparisonCoverage::query()
            ->where('owner_id', $ownerId)
            ->where('coverage_type', $coverageType)
            ->delete();
    }

    /**
     * Replace a slice with an authoritative set of pair counts, atomically. Rows
     * are {node_a_id, node_b_id, compared_count}; they are normalized + aggregated
     * here, so a caller may pass either orientation. Backs the backfill, the
     * drift-repair reconcile, and the read-path self-heal.
     *
     * @param iterable<array{node_a_id:int,node_b_id:int,compared_count:int}> $rows
     * @return int number of distinct pair rows written
     */
    public function replaceCoverageSlice(int $ownerId, string $coverageType, iterable $rows): int
    {
        $aggregated = [];
        foreach ($rows as $row) {
            [$a, $b] = $this->normalizePair((int) $row['node_a_id'], (int) $row['node_b_id']);
            $key = $a . '_' . $b;
            $aggregated[$key] = ($aggregated[$key] ?? 0) + (int) $row['compared_count'];
        }

        return DB::transaction(function () use ($ownerId, $coverageType, $aggregated): int {
            $this->clearCoverageSlice($ownerId, $coverageType);

            $now = now();
            $insert = [];
            foreach ($aggregated as $key => $count) {
                [$a, $b] = explode('_', $key);
                $insert[] = [
                    'owner_id' => $ownerId,
                    'coverage_type' => $coverageType,
                    'node_a_id' => (int) $a,
                    'node_b_id' => (int) $b,
                    'compared_count' => $count,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($insert, 500) as $batch) {
                DB::table('pairwise_comparison_coverage')->insert($batch);
            }

            return count($insert);
        });
    }

    /**
     * Normalize an unordered node pair so node_a_id <= node_b_id.
     *
     * @return array{0:int,1:int}
     */
    private function normalizePair(int $nodeAId, int $nodeBId): array
    {
        return $nodeAId <= $nodeBId ? [$nodeAId, $nodeBId] : [$nodeBId, $nodeAId];
    }
}
