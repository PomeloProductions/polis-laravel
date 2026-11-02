<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Statistic;

use App\Models\PlatformComparison;
use App\Models\Statistic\Statistic;
use App\Models\Statistic\StatisticDependency;
use App\Models\Statistic\TargetStatistic;
use Polis\Jobs\Statistic\RecountStatisticJob;
use Polis\Services\Statistic\DerivedStatisticService;
use Polis\Services\Statistic\MultiDimensionalStatisticService;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class DerivedStatisticTest
 *
 * Integration coverage for the MATERIALIZED derived-statistic layer: a derived
 * statistic is a ratio/difference/sum over other statistics' cells, recomputed
 * eagerly from the current operand cells (aligned by dimension-tuple), stored,
 * and read back as a value. Zero-denominator cells store null; derived-on-derived
 * is forbidden in v1.
 */
final class DerivedStatisticTest extends ApplicationTestCase
{
    private MultiDimensionalStatisticService $service;

    private DerivedStatisticService $derivedService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->service = app(MultiDimensionalStatisticService::class);
        $this->derivedService = app(DerivedStatisticService::class);
    }

    /**
     * @param  array<int, array<string, mixed>>  $dimensions
     * @param  array<string, mixed>  $measure
     */
    private function makeBase(string $name, array $dimensions, array $measure): Statistic
    {
        return Statistic::query()->create([
            'name' => $name,
            'model' => 'platform_comparison',
            'relation' => '',
            'kind' => 'count',
            'public' => true,
            'dimensions' => $dimensions,
            'measure' => $measure,
        ]);
    }

    private function makeDerived(string $name, string $operation, array $operandIds): Statistic
    {
        return Statistic::query()->create([
            'name' => $name,
            'model' => 'platform_comparison',
            'relation' => '',
            'kind' => 'derived',
            'public' => true,
            'derived_definition' => ['operation' => $operation, 'operands' => $operandIds],
        ]);
    }

    public function test_ratio_derived_statistic_materializes_per_cell(): void
    {
        // numerator: "best" comparisons by type; denominator: total by owner (one
        // cell, no extra axes) — but to align by tuple we use the SAME single axis
        // on both so cells match. Here: numerator counts 'best', denominator
        // counts all, both keyed by a constant axis so they align.
        PlatformComparison::factory()->count(3)->create(['owner_id' => 1, 'voting_type' => 'best']);
        PlatformComparison::factory()->count(1)->create(['owner_id' => 1, 'voting_type' => 'worst']);

        // Numerator: count where voting_type = best, keyed by owner only.
        $numerator = Statistic::query()->create([
            'name' => 'best_count', 'model' => 'platform_comparison', 'relation' => '',
            'kind' => 'count', 'public' => true,
            'dimensions' => [['name' => 'scope', 'kind' => 'scalar', 'column' => 'owner_id']],
            'measure' => ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        ]);
        $numerator->filters()->create(['field' => 'voting_type', 'operator' => '=', 'value' => 'best']);

        // Denominator: total count keyed by owner.
        $denominator = $this->makeBase(
            'total_count',
            [['name' => 'scope', 'kind' => 'scalar', 'column' => 'owner_id']],
            ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        );

        $this->service->recompute($numerator);
        $this->service->recompute($denominator);

        $derived = $this->makeDerived('best_ratio', 'ratio', [$numerator->id, $denominator->id]);
        $this->derivedService->syncDependencies($derived);
        $this->derivedService->recompute($derived);

        $cell = TargetStatistic::where('statistic_id', $derived->id)->where('target_id', 1)->first();
        $this->assertNotNull($cell);
        // 3 best / 4 total = 0.75
        $this->assertEqualsWithDelta(0.75, $cell->value, 0.0001);
        $this->assertTrue($cell->result['derived']);
    }

    public function test_difference_and_sum_derived_statistics(): void
    {
        PlatformComparison::factory()->count(5)->create(['owner_id' => 1, 'voting_type' => 'best']);
        PlatformComparison::factory()->count(2)->create(['owner_id' => 1, 'voting_type' => 'worst']);

        $best = Statistic::query()->create([
            'name' => 'best_c', 'model' => 'platform_comparison', 'relation' => '',
            'kind' => 'count', 'public' => true,
            'dimensions' => [['name' => 'scope', 'kind' => 'scalar', 'column' => 'owner_id']],
            'measure' => ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        ]);
        $best->filters()->create(['field' => 'voting_type', 'operator' => '=', 'value' => 'best']);

        $worst = Statistic::query()->create([
            'name' => 'worst_c', 'model' => 'platform_comparison', 'relation' => '',
            'kind' => 'count', 'public' => true,
            'dimensions' => [['name' => 'scope', 'kind' => 'scalar', 'column' => 'owner_id']],
            'measure' => ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        ]);
        $worst->filters()->create(['field' => 'voting_type', 'operator' => '=', 'value' => 'worst']);

        $this->service->recompute($best);
        $this->service->recompute($worst);

        $difference = $this->makeDerived('diff', 'difference', [$best->id, $worst->id]);
        $this->derivedService->syncDependencies($difference);
        $this->derivedService->recompute($difference);

        $sum = $this->makeDerived('sum', 'sum', [$best->id, $worst->id]);
        $this->derivedService->syncDependencies($sum);
        $this->derivedService->recompute($sum);

        $diffCell = TargetStatistic::where('statistic_id', $difference->id)->where('target_id', 1)->first();
        $sumCell = TargetStatistic::where('statistic_id', $sum->id)->where('target_id', 1)->first();

        $this->assertEquals(3.0, $diffCell->value); // 5 - 2
        $this->assertEquals(7.0, $sumCell->value);   // 5 + 2
    }

    public function test_zero_denominator_stores_null(): void
    {
        // Owner 1 has 'best' rows but NO 'worst' rows -> denominator cell is 0.
        PlatformComparison::factory()->count(2)->create(['owner_id' => 1, 'voting_type' => 'best']);

        $numerator = Statistic::query()->create([
            'name' => 'num', 'model' => 'platform_comparison', 'relation' => '',
            'kind' => 'count', 'public' => true,
            'dimensions' => [['name' => 'scope', 'kind' => 'scalar', 'column' => 'owner_id']],
            'measure' => ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        ]);
        $numerator->filters()->create(['field' => 'voting_type', 'operator' => '=', 'value' => 'best']);

        $denominator = Statistic::query()->create([
            'name' => 'den', 'model' => 'platform_comparison', 'relation' => '',
            'kind' => 'count', 'public' => true,
            'dimensions' => [['name' => 'scope', 'kind' => 'scalar', 'column' => 'owner_id']],
            'measure' => ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']],
        ]);
        $denominator->filters()->create(['field' => 'voting_type', 'operator' => '=', 'value' => 'worst']);

        $this->service->recompute($numerator);
        $this->service->recompute($denominator);

        $derived = $this->makeDerived('ratio_zero', 'ratio', [$numerator->id, $denominator->id]);
        $this->derivedService->syncDependencies($derived);
        $this->derivedService->recompute($derived);

        // The numerator cell exists for owner 1; the denominator cell does NOT
        // (no 'worst' rows) -> missing operand -> stored value null.
        $cell = TargetStatistic::where('statistic_id', $derived->id)->where('target_id', 1)->first();
        $this->assertNotNull($cell);
        $this->assertNull($cell->result['value']);
    }

    public function test_dependencies_are_persisted(): void
    {
        $a = $this->makeBase('a',
            [['name' => 'scope', 'kind' => 'scalar', 'column' => 'owner_id']],
            ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']]);
        $b = $this->makeBase('b',
            [['name' => 'scope', 'kind' => 'scalar', 'column' => 'owner_id']],
            ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']]);

        $derived = $this->makeDerived('d', 'ratio', [$a->id, $b->id]);
        $this->derivedService->syncDependencies($derived);

        $edges = StatisticDependency::where('statistic_id', $derived->id)->orderBy('position')->get();
        $this->assertCount(2, $edges);
        $this->assertSame($a->id, $edges[0]->depends_on_statistic_id);
        $this->assertSame($b->id, $edges[1]->depends_on_statistic_id);
    }

    public function test_recompute_is_triggered_on_base_recount_via_job(): void
    {
        PlatformComparison::factory()->count(4)->create(['owner_id' => 1, 'voting_type' => 'best']);

        $base = $this->makeBase('base_for_trigger',
            [['name' => 'scope', 'kind' => 'scalar', 'column' => 'owner_id']],
            ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']]);
        $other = $this->makeBase('other_for_trigger',
            [['name' => 'scope', 'kind' => 'scalar', 'column' => 'owner_id']],
            ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']]);

        $this->service->recompute($base);
        $this->service->recompute($other);

        $derived = $this->makeDerived('sum_trigger', 'sum', [$base->id, $other->id]);
        $this->derivedService->syncDependencies($derived);

        // Driving the base through the queued recount path must, at the end,
        // trigger the dependent derived recompute (sync queue runs it inline).
        RecountStatisticJob::dispatch($base);

        $cell = TargetStatistic::where('statistic_id', $derived->id)->where('target_id', 1)->first();
        $this->assertNotNull($cell);
        $this->assertEquals(8.0, $cell->value); // 4 + 4
    }

    public function test_derived_on_derived_is_forbidden_in_v1(): void
    {
        $base = $this->makeBase('bb',
            [['name' => 'scope', 'kind' => 'scalar', 'column' => 'owner_id']],
            ['agg' => 'count', 'target' => ['type' => 'user', 'id_column' => 'owner_id']]);

        $derived1 = $this->makeDerived('d1', 'sum', [$base->id]);
        $derived2 = $this->makeDerived('d2', 'sum', [$derived1->id]);

        $this->derivedService->syncDependencies($derived2);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Derived-on-derived/');
        $this->derivedService->recompute($derived2);
    }
}
