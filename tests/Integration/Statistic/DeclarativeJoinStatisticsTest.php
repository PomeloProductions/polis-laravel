<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Statistic;

use App\Models\Release;
use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use Illuminate\Support\Facades\DB;
use Polis\Services\Statistic\MultiDimensionalStatisticService;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class DeclarativeJoinStatisticsTest
 *
 * Integration coverage for DECLARATIVE JOINS in the GROUP BY engine: a statistic
 * counts ONE base table (`releases`) while its axes / filters / measure /
 * primary target reference RELATED tables via a declared `joins` list — the HSC
 * "year view" shape, which previously forced a bespoke per-shape SQL VIEW.
 *
 * Grounding: a release has no date column; its (regional) dates live in a joined
 * one-to-many `release_dates` table. count_distinct over releases.id keeps
 * "Total Releases" correct despite the fan-out. The category variant additionally
 * joins collection_items → collection_item_categories → collections, with the
 * primary target owner on the JOINED collections table.
 */
final class DeclarativeJoinStatisticsTest extends ApplicationTestCase
{
    private MultiDimensionalStatisticService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->service = app(MultiDimensionalStatisticService::class);
    }

    /**
     * @param  array<int, array<string, mixed>>  $dimensions
     * @param  array<string, mixed>  $measure
     * @param  array<int, array<string, mixed>>  $joins
     */
    private function makeStatistic(array $dimensions, array $measure, array $joins): Statistic
    {
        return Statistic::query()->create([
            'name' => 'joins_'.uniqid(),
            'model' => 'release',
            'relation' => '',
            'kind' => 'count',
            'public' => true,
            'dimensions' => $dimensions,
            'joins' => $joins,
            'measure' => $measure,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function release(array $attributes = []): Release
    {
        return Release::factory()->create($attributes);
    }

    private function addDate(int $releaseId, string $date, string $region = 'na'): void
    {
        DB::table('release_dates')->insert([
            'release_id' => $releaseId,
            'release_date' => $date,
            'region' => $region,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_join_and_group_by_a_joined_column(): void
    {
        // Two releases on platform 1, each with one 2021 date; one on platform 2
        // with a 2022 date.
        $r1 = $this->release(['platform_id' => 1]);
        $r2 = $this->release(['platform_id' => 1]);
        $r3 = $this->release(['platform_id' => 2]);
        $this->addDate($r1->id, '2021-05-01 00:00:00');
        $this->addDate($r2->id, '2021-09-01 00:00:00');
        $this->addDate($r3->id, '2022-01-01 00:00:00');

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'platform', 'kind' => 'scalar', 'column' => 'platform_id'],
                ['name' => 'year', 'kind' => 'date_bucket', 'column' => 'rd.release_date',
                    'granularity' => 'year', 'timezone' => 'UTC'],
            ],
            measure: ['agg' => 'count_distinct', 'column' => 'base.id'],
            joins: [
                ['table' => 'release_dates', 'alias' => 'rd',
                    'left' => 'base.id', 'right' => 'rd.release_id'],
            ],
        );

        $this->service->recompute($statistic);

        $cells = TargetStatistic::where('statistic_id', $statistic->id)->get();
        // (platform 1, 2021)=2, (platform 2, 2022)=1
        $this->assertCount(2, $cells);

        $p1 = $cells->first(fn ($c) => $c->result['tuple']['platform']['value'] == 1);
        $this->assertSame('2021', $p1->result['tuple']['year']['value']);
        $this->assertEquals(2.0, $p1->value);
    }

    public function test_count_distinct_over_one_to_many_join_does_not_inflate(): void
    {
        // ONE release on platform 1 with THREE regional 2021 dates. A naive
        // COUNT(*) over the join would report 3; count_distinct(base.id) = 1.
        $r1 = $this->release(['platform_id' => 1]);
        $this->addDate($r1->id, '2021-01-01 00:00:00', 'na');
        $this->addDate($r1->id, '2021-02-01 00:00:00', 'eu');
        $this->addDate($r1->id, '2021-03-01 00:00:00', 'jp');

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'platform', 'kind' => 'scalar', 'column' => 'platform_id'],
                ['name' => 'year', 'kind' => 'date_bucket', 'column' => 'rd.release_date',
                    'granularity' => 'year', 'timezone' => 'UTC'],
            ],
            measure: ['agg' => 'count_distinct', 'column' => 'base.id'],
            joins: [
                ['table' => 'release_dates', 'alias' => 'rd',
                    'left' => 'base.id', 'right' => 'rd.release_id'],
            ],
        );

        $this->service->recompute($statistic);

        $cell = TargetStatistic::where('statistic_id', $statistic->id)->first();
        $this->assertNotNull($cell);
        // 1 distinct release, NOT 3 fanned-out rows.
        $this->assertEquals(1.0, $cell->value);
    }

    public function test_primary_target_on_a_joined_table(): void
    {
        // Category variant: owner lives on the JOINED collections table. A
        // release in a collection owned by user 7, categorized under category 3.
        $r1 = $this->release(['platform_id' => 1]);

        $collectionId = DB::table('collections')->insertGetId([
            'owner_id' => 7, 'owner_type' => 'user', 'name' => 'Bucket List',
            'is_public' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $itemId = DB::table('collection_items')->insertGetId([
            'item_id' => $r1->id, 'item_type' => 'release', 'collection_id' => $collectionId,
            'order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('collection_item_categories')->insert([
            'collection_item_id' => $itemId, 'category_id' => 3,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'platform', 'kind' => 'scalar', 'column' => 'platform_id'],
            ],
            measure: [
                'agg' => 'count_distinct', 'column' => 'base.id',
                'target' => ['type' => 'user', 'id_column' => 'c.owner_id'],
            ],
            joins: [
                ['table' => 'collection_items', 'alias' => 'ci',
                    'left' => 'base.id', 'right' => 'ci.item_id',
                    'on_extra' => [['ci.item_type', '=', 'release']]],
                ['table' => 'collection_item_categories', 'alias' => 'cic',
                    'left' => 'ci.id', 'right' => 'cic.collection_item_id'],
                ['table' => 'collections', 'alias' => 'c',
                    'left' => 'ci.collection_id', 'right' => 'c.id'],
            ],
        );

        $this->service->recompute($statistic);

        $cell = TargetStatistic::where('statistic_id', $statistic->id)->first();
        $this->assertNotNull($cell);
        $this->assertSame('user', $cell->target_type);
        $this->assertEquals(7, (int) $cell->target_id); // owner on the JOINED table
        $this->assertEquals(1.0, $cell->value);
    }

    public function test_inner_join_soft_delete_guard_excludes_deleted_joined_row(): void
    {
        // One release with a LIVE 2021 date and a SOFT-DELETED 2022 date. The
        // inner-join soft-delete guard must exclude the deleted date, so only the
        // 2021 cell exists.
        $r1 = $this->release(['platform_id' => 1]);
        $this->addDate($r1->id, '2021-01-01 00:00:00');
        DB::table('release_dates')->insert([
            'release_id' => $r1->id, 'release_date' => '2022-01-01 00:00:00',
            'region' => 'eu', 'deleted_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'year', 'kind' => 'date_bucket', 'column' => 'rd.release_date',
                    'granularity' => 'year', 'timezone' => 'UTC'],
            ],
            measure: ['agg' => 'count_distinct', 'column' => 'base.id'],
            joins: [
                ['table' => 'release_dates', 'alias' => 'rd',
                    'left' => 'base.id', 'right' => 'rd.release_id', 'type' => 'inner'],
            ],
        );

        $this->service->recompute($statistic);

        $cells = TargetStatistic::where('statistic_id', $statistic->id)->get();
        $this->assertCount(1, $cells);
        $this->assertSame('2021', $cells->first()->result['tuple']['year']['value']);
    }

    public function test_left_join_keeps_unmatched_rows_with_guard_in_on_clause(): void
    {
        // Release A has a 2021 date; release B has ONLY a soft-deleted date. With
        // a LEFT join whose soft-delete guard sits IN the ON clause, release B
        // survives (as a NULL-year cell) instead of being dropped — proving the
        // LEFT join was NOT silently narrowed into an inner join.
        $a = $this->release(['platform_id' => 1]);
        $b = $this->release(['platform_id' => 1]);
        $this->addDate($a->id, '2021-01-01 00:00:00');
        DB::table('release_dates')->insert([
            'release_id' => $b->id, 'release_date' => '2022-01-01 00:00:00',
            'region' => 'na', 'deleted_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'year', 'kind' => 'date_bucket', 'column' => 'rd.release_date',
                    'granularity' => 'year', 'timezone' => 'UTC'],
            ],
            measure: ['agg' => 'count_distinct', 'column' => 'base.id'],
            joins: [
                ['table' => 'release_dates', 'alias' => 'rd',
                    'left' => 'base.id', 'right' => 'rd.release_id', 'type' => 'left'],
            ],
        );

        $this->service->recompute($statistic);

        $cells = TargetStatistic::where('statistic_id', $statistic->id)->get();
        // 2021 cell (release A) + a NULL-year cell (release B, unmatched date).
        $this->assertCount(2, $cells);

        $nullYear = $cells->first(fn ($c) => $c->result['tuple']['year']['value'] === null
            || $c->result['tuple']['year']['value'] === '');
        $this->assertNotNull($nullYear, 'LEFT join must keep the unmatched release.');
        $this->assertEquals(1.0, $nullYear->value);
    }

    public function test_on_extra_condition_filters_the_join(): void
    {
        // Two collection_items point at the release: one item_type 'release', one
        // 'game'. The on_extra ci.item_type='release' must keep only the release
        // link, so count_distinct(base.id)=1 and NOT inflated by the stray row.
        $r1 = $this->release(['platform_id' => 1]);
        $collectionId = DB::table('collections')->insertGetId([
            'owner_id' => 9, 'owner_type' => 'user', 'name' => 'C',
            'is_public' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('collection_items')->insert([
            'item_id' => $r1->id, 'item_type' => 'release', 'collection_id' => $collectionId,
            'order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        // A stray row with the SAME item_id but a different item_type.
        DB::table('collection_items')->insert([
            'item_id' => $r1->id, 'item_type' => 'game', 'collection_id' => $collectionId,
            'order' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'platform', 'kind' => 'scalar', 'column' => 'platform_id'],
            ],
            measure: ['agg' => 'count_distinct', 'column' => 'base.id'],
            joins: [
                ['table' => 'collection_items', 'alias' => 'ci',
                    'left' => 'base.id', 'right' => 'ci.item_id',
                    'on_extra' => [['ci.item_type', '=', 'release']]],
            ],
        );

        $this->service->recompute($statistic);

        $cell = TargetStatistic::where('statistic_id', $statistic->id)->first();
        $this->assertNotNull($cell);
        // The 'game' row is filtered out by on_extra; distinct release = 1.
        $this->assertEquals(1.0, $cell->value);
    }

    public function test_sub_select_join_derives_and_groups_by_earliest_year(): void
    {
        // The HSC "earliest year" shape: a release has MANY regional dates; we
        // want to bucket each release by the EARLIEST (MIN) of its release dates.
        // A grouped sub-select derives one earliest_year per release, then the
        // engine groups by that derived column.
        //
        //   r1 (platform 1): 2020-06, 2021-01       -> earliest 2020
        //   r2 (platform 1): 2020-11                 -> earliest 2020
        //   r3 (platform 2): 2022-03, 2019-12        -> earliest 2019
        $r1 = $this->release(['platform_id' => 1]);
        $r2 = $this->release(['platform_id' => 1]);
        $r3 = $this->release(['platform_id' => 2]);
        $this->addDate($r1->id, '2020-06-01 00:00:00');
        $this->addDate($r1->id, '2021-01-01 00:00:00');
        $this->addDate($r2->id, '2020-11-01 00:00:00');
        $this->addDate($r3->id, '2022-03-01 00:00:00');
        $this->addDate($r3->id, '2019-12-01 00:00:00');

        // A soft-deleted EARLIER date on r1 that the sub-select's own WHERE must
        // exclude (proving the sub-select owns its filtering — no engine guard).
        DB::table('release_dates')->insert([
            'release_id' => $r1->id, 'release_date' => '2015-01-01 00:00:00',
            'region' => 'jp', 'deleted_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Driver-aware YEAR(MIN(...)): SQLite has no YEAR(); use strftime.
        $driver = DB::connection()->getDriverName();
        $earliestYear = $driver === 'sqlite'
            ? "CAST(strftime('%Y', MIN(release_date)) AS INTEGER)"
            : 'YEAR(MIN(release_date))';

        $subSelect = "SELECT release_id, {$earliestYear} AS earliest_year "
            .'FROM release_dates '
            .'WHERE release_date IS NOT NULL AND deleted_at IS NULL '
            .'GROUP BY release_id';

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'earliest_year', 'kind' => 'scalar', 'column' => 'ey.earliest_year'],
            ],
            measure: ['agg' => 'count_distinct', 'column' => 'base.id'],
            joins: [
                ['table_raw' => $subSelect, 'alias' => 'ey',
                    'left' => 'base.id', 'right' => 'ey.release_id'],
            ],
        );

        $this->service->recompute($statistic);

        $cells = TargetStatistic::where('statistic_id', $statistic->id)->get();
        // Two earliest-year groups: 2020 (r1 + r2 = 2) and 2019 (r3 = 1).
        $this->assertCount(2, $cells);

        $y2020 = $cells->first(
            fn ($c) => (int) $c->result['tuple']['earliest_year']['value'] === 2020
        );
        $this->assertNotNull($y2020, 'Expected a 2020 earliest-year cell.');
        $this->assertEquals(2.0, $y2020->value);

        $y2019 = $cells->first(
            fn ($c) => (int) $c->result['tuple']['earliest_year']['value'] === 2019
        );
        $this->assertNotNull($y2019, 'Expected a 2019 earliest-year cell.');
        $this->assertEquals(1.0, $y2019->value);
    }

    public function test_qualified_static_filter_on_a_joined_column(): void
    {
        // Confirm a static filter on a QUALIFIED joined column works end-to-end:
        // filter cic.category_id = 3 keeps only the category-3 release.
        $r1 = $this->release(['platform_id' => 1]);
        $r2 = $this->release(['platform_id' => 1]);

        foreach ([[$r1->id, 3], [$r2->id, 5]] as [$releaseId, $categoryId]) {
            $collectionId = DB::table('collections')->insertGetId([
                'owner_id' => 1, 'owner_type' => 'user', 'name' => 'C',
                'is_public' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $itemId = DB::table('collection_items')->insertGetId([
                'item_id' => $releaseId, 'item_type' => 'release', 'collection_id' => $collectionId,
                'order' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('collection_item_categories')->insert([
                'collection_item_id' => $itemId, 'category_id' => $categoryId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $statistic = $this->makeStatistic(
            dimensions: [
                ['name' => 'platform', 'kind' => 'scalar', 'column' => 'platform_id'],
            ],
            measure: ['agg' => 'count_distinct', 'column' => 'base.id'],
            joins: [
                ['table' => 'collection_items', 'alias' => 'ci',
                    'left' => 'base.id', 'right' => 'ci.item_id',
                    'on_extra' => [['ci.item_type', '=', 'release']]],
                ['table' => 'collection_item_categories', 'alias' => 'cic',
                    'left' => 'ci.id', 'right' => 'cic.collection_item_id'],
            ],
        );

        // Static filter on a qualified joined column.
        $statistic->filters()->create([
            'field' => 'cic.category_id', 'operator' => '=', 'value' => '3',
        ]);
        $statistic->load('filters');

        $this->service->recompute($statistic);

        $cell = TargetStatistic::where('statistic_id', $statistic->id)->first();
        $this->assertNotNull($cell);
        // Only the category-3 release counts.
        $this->assertEquals(1.0, $cell->value);
    }
}
