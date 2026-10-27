<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Statistic;

use App\Models\PlatformComparison;
use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use App\Models\Statistic\TargetStatisticDimension;
use Polis\Services\Statistic\MultiDimensionalStatisticService;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class MultiDimensionalStatisticsTest
 *
 * Integration coverage for the GROUP BY aggregation engine: multi-dim cell
 * materialization, partial-tuple slice reads, single-axis rollup/SUM,
 * count_distinct grain, the soft-delete guard (scope-bypass regression), and the
 * date_bucket axis. Runs against the dummy consumer app's platform_comparisons
 * ledger.
 */
final class MultiDimensionalStatisticsTest extends ApplicationTestCase
{
    private MultiDimensionalStatisticService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->service = app(MultiDimensionalStatisticService::class);
    }

    /**
     * Create an engine-backed statistic over the platform_comparisons ledger.
     *
     * @param  array<int, array<string, mixed>>  $dimensions
     * @param  array<string, mixed>  $measure
     */
    private function makeStatistic(array $dimensions, array $measure): Statistic
    {
        return Statistic::query()->create([
            'name' => 'multidim_'.uniqid(),
            'model' => 'platform_comparison',
            'relation' => '',
            'kind' => 'count',
            'public' => true,
            'dimensions' => $dimensions,
            'measure' => $measure,
        ]);
    }

    public function test_materializes_a_cell_per_owner_and_category_combination(): void
    {
        // owner 1: 2 "best" + 1 "worst"; owner 2: 1 "best"
        PlatformComparison::factory()->create(['owner_id' => 1, 'voting_type' => 'best']);
        PlatformComparison::factory()->create(['owner_id' => 1, 'voting_type' => 'best']);
        PlatformComparison::factory()->create(['owner_id' => 1, 'voting_type' => 'worst']);
        PlatformComparison::factory()->create(['owner_id' => 2, 'voting_type' => 'best']);

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'voting_type', 'kind' => 'scalar', 'column' => 'voting_type'],
            ],
            measure: ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        );

        $this->service->recompute($statistic);

        // 3 cells: (1,best)=2, (1,worst)=1, (2,best)=1
        $cells = TargetStatistic::where('statistic_id', $statistic->id)->get();
        $this->assertCount(3, $cells);

        $owner1Best = $cells->first(fn ($c) => $c->target_id == 1 && $c->result['tuple']['voting_type']['value'] === 'best');
        $this->assertEquals(2.0, $owner1Best->value);
        $this->assertSame('user', $owner1Best->target_type);
    }

    public function test_partial_tuple_slice_reads_via_dimension_child_rows(): void
    {
        PlatformComparison::factory()->create(['owner_id' => 1, 'voting_type' => 'best']);
        PlatformComparison::factory()->create(['owner_id' => 1, 'voting_type' => 'worst']);

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'voting_type', 'kind' => 'scalar', 'column' => 'voting_type'],
            ],
            measure: ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        );

        $this->service->recompute($statistic);

        // Slice: all cells where axis voting_type = 'best' — served off the child
        // table by (statistic_id, axis, value_scalar).
        $bestDimensionRows = TargetStatisticDimension::where('statistic_id', $statistic->id)
            ->where('axis', 'voting_type')
            ->where('value_scalar', 'best')
            ->get();

        $this->assertCount(1, $bestDimensionRows);
        $this->assertEquals(
            1.0,
            TargetStatistic::find($bestDimensionRows->first()->target_statistic_id)->value,
        );
    }

    public function test_rollup_sum_across_one_axis(): void
    {
        PlatformComparison::factory()->create(['owner_id' => 1, 'voting_type' => 'best']);
        PlatformComparison::factory()->create(['owner_id' => 1, 'voting_type' => 'best']);
        PlatformComparison::factory()->create(['owner_id' => 1, 'voting_type' => 'worst']);

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'voting_type', 'kind' => 'scalar', 'column' => 'voting_type'],
            ],
            measure: ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        );

        $this->service->recompute($statistic);

        // Rolling up across the voting_type axis for owner 1 = SUM of the scalar
        // cell values = total comparisons = 3.
        $total = (float) TargetStatistic::where('statistic_id', $statistic->id)
            ->where('target_id', 1)
            ->sum('value');

        $this->assertEquals(3.0, $total);
    }

    public function test_count_distinct_grain_does_not_inflate_on_fan_out(): void
    {
        // Two comparison rows sharing the same comparison_group_id — a
        // count_distinct over the group must count the group ONCE.
        PlatformComparison::factory()->create([
            'owner_id' => 1, 'voting_type' => 'best', 'comparison_group_id' => 100,
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 1, 'voting_type' => 'best', 'comparison_group_id' => 100,
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 1, 'voting_type' => 'best', 'comparison_group_id' => 200,
        ]);

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'voting_type', 'kind' => 'scalar', 'column' => 'voting_type'],
            ],
            measure: [
                'agg' => 'count_distinct',
                'column' => 'comparison_group_id',
                'target' => ['type' => 'user', 'id_column' => 'owner_id'],
            ],
        );

        $this->service->recompute($statistic);

        $cell = TargetStatistic::where('statistic_id', $statistic->id)
            ->where('target_id', 1)
            ->first();

        // 2 distinct groups (100, 200), not 3 rows.
        $this->assertEquals(2.0, $cell->value);
    }

    public function test_soft_deleted_rows_are_excluded_scope_bypass_regression(): void
    {
        PlatformComparison::factory()->create(['owner_id' => 1, 'voting_type' => 'best']);
        $deleted = PlatformComparison::factory()->create(['owner_id' => 1, 'voting_type' => 'best']);
        $deleted->delete(); // soft delete

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'voting_type', 'kind' => 'scalar', 'column' => 'voting_type'],
            ],
            measure: ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        );

        $this->service->recompute($statistic);

        $cell = TargetStatistic::where('statistic_id', $statistic->id)
            ->where('target_id', 1)
            ->first();

        // The soft-deleted row must NOT be counted — the guard is baked into the
        // GROUP BY, not inherited from the (bypassed) global scope.
        $this->assertEquals(1.0, $cell->value);
    }

    public function test_date_bucket_axis_groups_by_day(): void
    {
        PlatformComparison::factory()->create([
            'owner_id' => 1, 'voting_type' => 'best', 'compared_at' => '2026-10-01 10:00:00',
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 1, 'voting_type' => 'best', 'compared_at' => '2026-10-01 20:00:00',
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 1, 'voting_type' => 'best', 'compared_at' => '2026-10-02 09:00:00',
        ]);

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'day', 'kind' => 'date_bucket', 'column' => 'compared_at',
                    'granularity' => 'day', 'timezone' => 'UTC'],
            ],
            measure: ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        );

        $this->service->recompute($statistic);

        $cells = TargetStatistic::where('statistic_id', $statistic->id)->get();
        $this->assertCount(2, $cells); // 2026-10-01 and 2026-10-02

        $oct1 = $cells->first(fn ($c) => $c->result['tuple']['day']['value'] === '2026-10-01');
        $this->assertEquals(2.0, $oct1->value);
    }

    public function test_date_bucket_axis_respects_timezone(): void
    {
        // 03:00 UTC on Oct 2 is 23:00 Oct 1 in America/New_York (UTC-4 in Oct).
        PlatformComparison::factory()->create([
            'owner_id' => 1, 'voting_type' => 'best', 'compared_at' => '2026-10-02 03:00:00',
        ]);

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'day', 'kind' => 'date_bucket', 'column' => 'compared_at',
                    'granularity' => 'day', 'timezone' => 'America/New_York'],
            ],
            measure: ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        );

        $this->service->recompute($statistic);

        $cell = TargetStatistic::where('statistic_id', $statistic->id)->first();
        // Bucketed in the user's tz -> lands on Oct 1, not the UTC Oct 2.
        $this->assertSame('2026-10-01', $cell->result['tuple']['day']['value']);
    }

    public function test_date_bucket_week_axis_groups_by_week_start(): void
    {
        // 2026-10-25 is a Sunday, 2026-10-27 a Tuesday, 2026-11-01 the next Sunday.
        // With a Sunday week-start, the first two share a week; the third opens a
        // new one.
        PlatformComparison::factory()->create([
            'owner_id' => 1, 'voting_type' => 'best', 'compared_at' => '2026-10-25 10:00:00',
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 1, 'voting_type' => 'best', 'compared_at' => '2026-10-27 10:00:00',
        ]);
        PlatformComparison::factory()->create([
            'owner_id' => 1, 'voting_type' => 'best', 'compared_at' => '2026-11-01 10:00:00',
        ]);

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'week', 'kind' => 'date_bucket', 'column' => 'compared_at',
                    'granularity' => 'week', 'timezone' => 'UTC', 'week_start' => 0],
            ],
            measure: ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        );

        $this->service->recompute($statistic);

        $cells = TargetStatistic::where('statistic_id', $statistic->id)->get();
        $this->assertCount(2, $cells); // week of 10-25 (count 2) + week of 11-01 (count 1)

        $firstWeek = $cells->first(fn ($c) => $c->result['tuple']['week']['value'] === 'W-2026-10-25');
        $this->assertNotNull($firstWeek);
        $this->assertEquals(2.0, $firstWeek->value);
    }

    public function test_recompute_is_sparse_and_prunes_vanished_cells(): void
    {
        $row = PlatformComparison::factory()->create(['owner_id' => 1, 'voting_type' => 'best']);

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'voting_type', 'kind' => 'scalar', 'column' => 'voting_type'],
            ],
            measure: ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        );

        $this->service->recompute($statistic);
        $this->assertSame(1, TargetStatistic::where('statistic_id', $statistic->id)->count());

        // Remove the only row -> that cell must be pruned on the next recompute.
        $row->forceDelete();
        $this->service->recompute($statistic);

        $this->assertSame(0, TargetStatistic::where('statistic_id', $statistic->id)->count());
    }
}
