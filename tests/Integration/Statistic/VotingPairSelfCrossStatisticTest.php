<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Statistic;

use App\Models\PlatformComparison;
use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use Polis\Services\Statistic\MultiDimensionalStatisticService;
use Polis\Support\Statistic\DimensionTuple;
use Polis\Support\Statistic\DimensionValue;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class VotingPairSelfCrossStatisticTest
 *
 * ACCEPTANCE PROOF (spec §6): the package can express HSC's voting_pair_stats
 * shape — (user × voting_type × platform_a ≤ platform_b, compared_count) — a
 * self-cross of one entity type against itself with an UNORDERED canonical pair,
 * materialized and incrementally maintained. If these pass, HSC's bespoke
 * voting_pair_stats table/maintainer/reader/backfill can later fold onto this
 * primitive.
 */
final class VotingPairSelfCrossStatisticTest extends ApplicationTestCase
{
    private MultiDimensionalStatisticService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->service = app(MultiDimensionalStatisticService::class);
    }

    /**
     * The voting-pair statistic: primary axis = user (owner_id); extra axes =
     * voting_type (scalar) + the unordered platform pair (platform_a, platform_b
     * sharing a pair_group so (A,B) and (B,A) collapse to one cell).
     */
    private function makeVotingPairStatistic(): Statistic
    {
        return Statistic::query()->create([
            'name' => 'voting_pair_coverage',
            'model' => 'platform_comparison',
            'relation' => '',
            'kind' => 'count',
            'public' => true,
            'dimensions' => [
                ['name' => 'voting_type', 'kind' => 'scalar', 'column' => 'voting_type'],
                ['name' => 'platform_a', 'kind' => 'entity', 'id_column' => 'platform_a_id',
                    'type' => 'platform', 'pair_group' => 'platforms'],
                ['name' => 'platform_b', 'kind' => 'entity', 'id_column' => 'platform_b_id',
                    'type' => 'platform', 'pair_group' => 'platforms'],
            ],
            'measure' => ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        ]);
    }

    /**
     * The canonical dimension_key for a (voting_type, unordered pair) cell, built
     * through the SAME canonicalizer the engine uses.
     */
    private function cellKey(string $votingType, int $platformA, int $platformB): string
    {
        return (new DimensionTuple([
            'voting_type' => DimensionValue::scalar($votingType),
            'platform_a' => DimensionValue::entity('platform', $platformA),
            'platform_b' => DimensionValue::entity('platform', $platformB),
        ], [['platform_a', 'platform_b']]))->canonicalized()->key();
    }

    public function test_unordered_pair_collapses_to_one_cell(): void
    {
        // (1,2) and (2,1) for the same user+type are the SAME unordered pair.
        PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'best', 'platform_a_id' => 1, 'platform_b_id' => 2,
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'best', 'platform_a_id' => 2, 'platform_b_id' => 1,
        ]);

        $statistic = $this->makeVotingPairStatistic();
        $this->service->recompute($statistic);

        $cells = TargetStatistic::where('statistic_id', $statistic->id)->get();

        // One canonical cell, compared_count = 2.
        $this->assertCount(1, $cells);
        $this->assertEquals(2.0, $cells->first()->value);
        $this->assertSame(
            $this->cellKey('best', 1, 2),
            $cells->first()->dimension_key,
        );
    }

    public function test_distinct_users_types_and_pairs_get_distinct_cells(): void
    {
        PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'best', 'platform_a_id' => 1, 'platform_b_id' => 2,
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'worst', 'platform_a_id' => 1, 'platform_b_id' => 2,
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 8, 'voting_type' => 'best', 'platform_a_id' => 1, 'platform_b_id' => 2,
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'best', 'platform_a_id' => 3, 'platform_b_id' => 4,
        ]);

        $statistic = $this->makeVotingPairStatistic();
        $this->service->recompute($statistic);

        // 4 distinct (user, type, pair) cells.
        $this->assertSame(4, TargetStatistic::where('statistic_id', $statistic->id)->count());
    }

    public function test_incremental_maintenance_increments_a_pair_cell_on_write(): void
    {
        $statistic = $this->makeVotingPairStatistic();

        // First comparison write — the observer's incremental path materializes
        // the cell (no explicit recompute call).
        PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'best', 'platform_a_id' => 2, 'platform_b_id' => 1,
        ]);

        $cell = TargetStatistic::where('statistic_id', $statistic->id)
            ->where('target_id', 7)
            ->where('dimension_key', $this->cellKey('best', 1, 2))
            ->first();
        $this->assertNotNull($cell);
        $this->assertEquals(1.0, $cell->value);

        // Second write of the same unordered pair increments the SAME cell.
        PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'best', 'platform_a_id' => 1, 'platform_b_id' => 2,
        ]);

        $cell->refresh();
        $this->assertEquals(2.0, $cell->value);
        $this->assertSame(
            1,
            TargetStatistic::where('statistic_id', $statistic->id)->where('target_id', 7)->count(),
        );
    }

    public function test_incremental_maintenance_decrements_on_soft_delete(): void
    {
        $statistic = $this->makeVotingPairStatistic();

        $first = PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'best', 'platform_a_id' => 1, 'platform_b_id' => 2,
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'best', 'platform_a_id' => 1, 'platform_b_id' => 2,
        ]);

        $key = $this->cellKey('best', 1, 2);
        $cell = TargetStatistic::where('statistic_id', $statistic->id)
            ->where('dimension_key', $key)->first();
        $this->assertEquals(2.0, $cell->value);

        // Soft-deleting one comparison must decrement the pair cell to 1 (the
        // soft-delete guard excludes the trashed row from the recomputed count).
        $first->delete();

        $cell->refresh();
        $this->assertEquals(1.0, $cell->value);
    }

    public function test_incremental_maintenance_prunes_cell_when_last_comparison_removed(): void
    {
        $statistic = $this->makeVotingPairStatistic();

        $only = PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'best', 'platform_a_id' => 1, 'platform_b_id' => 2,
        ]);

        $this->assertSame(1, TargetStatistic::where('statistic_id', $statistic->id)->count());

        $only->delete();

        // The cell's only comparison is gone -> the cell is pruned for that scope.
        $this->assertSame(0, TargetStatistic::where('statistic_id', $statistic->id)->count());
    }

    public function test_backfill_recompute_reproduces_all_pair_cells(): void
    {
        // Seed comparisons BEFORE the statistic exists (so no incremental path
        // ran) — then a backfill recompute must reproduce every pair cell.
        PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'best', 'platform_a_id' => 2, 'platform_b_id' => 1,
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'best', 'platform_a_id' => 1, 'platform_b_id' => 2,
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 7, 'voting_type' => 'best', 'platform_a_id' => 3, 'platform_b_id' => 5,
        ]);

        $statistic = $this->makeVotingPairStatistic();
        $this->service->recompute($statistic);

        $pair12 = TargetStatistic::where('statistic_id', $statistic->id)
            ->where('dimension_key', $this->cellKey('best', 1, 2))->first();
        $pair35 = TargetStatistic::where('statistic_id', $statistic->id)
            ->where('dimension_key', $this->cellKey('best', 3, 5))->first();

        $this->assertEquals(2.0, $pair12->value);
        $this->assertEquals(1.0, $pair35->value);
        $this->assertSame(2, TargetStatistic::where('statistic_id', $statistic->id)->count());
    }
}
