<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Statistic;

use Polis\Models\PairwiseComparisonCoverage;
use Polis\Services\Statistic\MaintainsPairwiseCoverage;
use Polis\Services\Statistic\PairwiseCoverageMatrix;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Integration tests for the reusable pairwise comparison coverage primitive:
 * incremental maintenance (increment / decrement with delete-at-zero / pair
 * normalization / slice clear), full-slice replace (backfill + drift repair), and
 * the read matrix with its within / across sums.
 */
final class PairwiseCoverageTest extends ApplicationTestCase
{
    /** @var object{incrementCoverage:callable} */
    private object $maintainer;

    private PairwiseCoverageMatrix $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        // Expose the trait via an anonymous host so tests drive it directly.
        $this->maintainer = new class {
            use MaintainsPairwiseCoverage;
        };
        $this->reader = new PairwiseCoverageMatrix();
    }

    private function storedCount(int $a, int $b): ?int
    {
        [$lo, $hi] = $a <= $b ? [$a, $b] : [$b, $a];

        return PairwiseComparisonCoverage::query()
            ->where('owner_id', 1)
            ->where('coverage_type', 'votes')
            ->where('node_a_id', $lo)
            ->where('node_b_id', $hi)
            ->value('compared_count');
    }

    public function test_increment_creates_then_accumulates_and_normalizes(): void
    {
        $this->maintainer->incrementCoverage(1, 'votes', 20, 10); // reversed order
        $this->maintainer->incrementCoverage(1, 'votes', 10, 20);

        $rows = PairwiseComparisonCoverage::query()->where('owner_id', 1)->get();
        $this->assertCount(1, $rows, 'Both orientations collapse to one normalized row');
        $this->assertSame(10, $rows->first()->node_a_id);
        $this->assertSame(20, $rows->first()->node_b_id);
        $this->assertSame(2, $rows->first()->compared_count);
    }

    public function test_self_pair_on_the_diagonal(): void
    {
        $this->maintainer->incrementCoverage(1, 'votes', 7, 7);
        $this->assertSame(1, $this->storedCount(7, 7));
    }

    public function test_decrement_symmetric_and_deletes_at_zero(): void
    {
        $this->maintainer->incrementCoverage(1, 'votes', 10, 20);
        $this->maintainer->incrementCoverage(1, 'votes', 10, 20);
        $this->maintainer->decrementCoverage(1, 'votes', 20, 10);
        $this->assertSame(1, $this->storedCount(10, 20));

        $this->maintainer->decrementCoverage(1, 'votes', 10, 20);
        $this->assertNull($this->storedCount(10, 20));
    }

    public function test_decrement_missing_pair_is_noop(): void
    {
        $this->maintainer->decrementCoverage(1, 'votes', 10, 20);
        $this->assertSame(0, PairwiseComparisonCoverage::query()->count());
    }

    public function test_clear_slice_is_scoped(): void
    {
        $this->maintainer->incrementCoverage(1, 'votes', 10, 20);
        $this->maintainer->incrementCoverage(1, 'other', 10, 20);
        $this->maintainer->incrementCoverage(2, 'votes', 10, 20);

        $this->maintainer->clearCoverageSlice(1, 'votes');

        $this->assertSame(0, PairwiseComparisonCoverage::query()->where('owner_id', 1)->where('coverage_type', 'votes')->count());
        $this->assertSame(1, PairwiseComparisonCoverage::query()->where('owner_id', 1)->where('coverage_type', 'other')->count());
        $this->assertSame(1, PairwiseComparisonCoverage::query()->where('owner_id', 2)->where('coverage_type', 'votes')->count());
    }

    public function test_replace_slice_is_idempotent_and_repairs_drift(): void
    {
        // Seed a wrong state.
        $this->maintainer->incrementCoverage(1, 'votes', 10, 20);
        PairwiseComparisonCoverage::query()->where('owner_id', 1)->update(['compared_count' => 999]);

        $rows = [
            ['node_a_id' => 20, 'node_b_id' => 10, 'compared_count' => 2], // reversed, aggregates
            ['node_a_id' => 10, 'node_b_id' => 20, 'compared_count' => 1],
            ['node_a_id' => 7, 'node_b_id' => 7, 'compared_count' => 4],
        ];

        $written = $this->maintainer->replaceCoverageSlice(1, 'votes', $rows);
        $this->assertSame(2, $written);
        $this->assertSame(3, $this->storedCount(10, 20)); // 2 + 1 aggregated
        $this->assertSame(4, $this->storedCount(7, 7));

        // Re-run converges (idempotent).
        $this->maintainer->replaceCoverageSlice(1, 'votes', $rows);
        $this->assertSame(2, PairwiseComparisonCoverage::query()->where('owner_id', 1)->count());
        $this->assertSame(3, $this->storedCount(10, 20));
    }

    public function test_matrix_within_and_across_sums(): void
    {
        // nodes 1,2 in group A; node 3 in group B.
        $this->maintainer->incrementCoverage(1, 'votes', 1, 2); // within A
        $this->maintainer->incrementCoverage(1, 'votes', 1, 2); // within A again
        $this->maintainer->incrementCoverage(1, 'votes', 1, 1); // self A
        $this->maintainer->incrementCoverage(1, 'votes', 2, 3); // across
        $this->maintainer->incrementCoverage(1, 'votes', 1, 3); // across

        $matrix = $this->reader->forOwner(1, 'votes');

        $this->assertSame(3, $this->reader->sumWithin($matrix, [1, 2]));          // 2 (1-2) + 1 (1-1)
        $this->assertSame(2, $this->reader->sumAcross($matrix, [1, 2], [3]));     // 2-3 and 1-3
        $this->assertSame(0, $this->reader->sumWithin($matrix, [3]));
    }
}
