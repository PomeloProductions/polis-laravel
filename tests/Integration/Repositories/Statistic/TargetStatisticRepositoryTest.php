<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Statistic;

use App\Models\Collection\Collection;
use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Polis\Repositories\Statistic\TargetStatisticRepository;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class TargetStatisticRepositoryTest
 */
class TargetStatisticRepositoryTest extends ApplicationTestCase
{
    /**
     * @var TargetStatisticRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new TargetStatisticRepository(
            new TargetStatistic,
            $this->getGenericLogMock()
        );
    }

    public function test_find_all_returns_collection()
    {
        foreach (TargetStatistic::all() as $model) {
            $model->delete();
        }
        TargetStatistic::factory()->count(5)->create();

        $models = $this->repository->findAll();

        $this->assertCount(5, $models);
        $this->assertInstanceOf(LengthAwarePaginator::class, $models);
    }

    public function test_find_all_with_filter_returns_collection()
    {
        foreach (TargetStatistic::all() as $model) {
            $model->delete();
        }
        TargetStatistic::factory()->create(['id' => 1]);
        TargetStatistic::factory()->count(4)->create();

        $models = $this->repository->findAll(['id' => 1]);

        $this->assertCount(1, $models);
        $this->assertInstanceOf(LengthAwarePaginator::class, $models);
    }

    public function test_find_returns_model()
    {
        foreach (TargetStatistic::all() as $model) {
            $model->delete();
        }
        $model = TargetStatistic::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);

        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_throws_exception()
    {
        foreach (TargetStatistic::all() as $model) {
            $model->delete();
        }
        TargetStatistic::factory()->create(['id' => 35]);

        $this->expectException(\Exception::class);

        $this->repository->findOrFail(1);
    }

    public function test_create_for_target_success()
    {
        $collection = Collection::factory()->create();
        $statistic = Statistic::factory()->create();

        $targetStatistic = $this->repository->createForTarget($collection, [
            'statistic_id' => $statistic->id,
            'value' => 42.5,
            'result' => ['type' => 'test'],
        ]);

        $this->assertInstanceOf(TargetStatistic::class, $targetStatistic);
        $this->assertEquals($collection->id, $targetStatistic->target_id);
        $this->assertEquals('collection', $targetStatistic->target_type);
        $this->assertEquals($statistic->id, $targetStatistic->statistic_id);
        $this->assertEquals(42.5, $targetStatistic->value);
        $this->assertEquals(['type' => 'test'], $targetStatistic->result);
    }

    public function test_find_all_for_target_success()
    {
        $collection = Collection::factory()->create();
        TargetStatistic::factory()->count(3)->create([
            'target_id' => $collection->id,
            'target_type' => 'collection',
        ]);
        // Create some stats for another collection to ensure filtering works
        TargetStatistic::factory()->count(2)->create();

        $results = $this->repository->findAllForTarget($collection);

        $this->assertCount(3, $results);
        $this->assertInstanceOf(EloquentCollection::class, $results);
        foreach ($results as $stat) {
            $this->assertEquals($collection->id, $stat->target_id);
            $this->assertEquals('collection', $stat->target_type);
        }
    }

    public function test_find_for_target_success()
    {
        $collection = Collection::factory()->create();
        $statistic = Statistic::factory()->create();
        TargetStatistic::factory()->create([
            'target_id' => $collection->id,
            'target_type' => 'collection',
            'statistic_id' => $statistic->id,
        ]);

        $result = $this->repository->findForTarget($collection, $statistic->id);

        $this->assertNotNull($result);
        $this->assertInstanceOf(TargetStatistic::class, $result);
        $this->assertEquals($collection->id, $result->target_id);
        $this->assertEquals($statistic->id, $result->statistic_id);
    }

    public function test_find_for_target_returns_null_when_not_found()
    {
        $collection = Collection::factory()->create();
        $result = $this->repository->findForTarget($collection, 999);

        $this->assertNull($result);
    }

    public function test_create_success(): void
    {
        $statistic = Statistic::factory()->create();

        $targetStatistic = $this->repository->create([
            'statistic_id' => $statistic->id,
            'target_type' => User::class,
            'target_id' => 1,
            'result' => ['test' => 'value'],
            'value' => 1.0,
        ]);

        $this->assertInstanceOf(TargetStatistic::class, $targetStatistic);
        $this->assertEquals($statistic->id, $targetStatistic->statistic_id);
        $this->assertEquals(User::class, $targetStatistic->target_type);
        $this->assertEquals(1, $targetStatistic->target_id);
        $this->assertEquals(['test' => 'value'], $targetStatistic->result);
        $this->assertEquals(1.0, $targetStatistic->value);
    }

    public function test_update_success(): void
    {
        $targetStatistic = TargetStatistic::factory()->create([
            'result' => ['old' => 'value'],
            'value' => 1.0,
        ]);

        $updatedStatistic = $this->repository->update($targetStatistic, [
            'result' => ['new' => 'value'],
            'value' => 2.0,
        ]);

        $this->assertInstanceOf(TargetStatistic::class, $updatedStatistic);
        $this->assertEquals(['new' => 'value'], $updatedStatistic->result);
        $this->assertEquals(2.0, $updatedStatistic->value);
    }

    public function test_delete_success()
    {
        $targetStatistic = TargetStatistic::factory()->create();

        $this->repository->delete($targetStatistic);

        $this->assertNull(TargetStatistic::find($targetStatistic->id));
    }

    public function test_upsert_cell_creates_cell_with_dimension_rows(): void
    {
        $statistic = Statistic::factory()->create();

        $cell = $this->repository->upsertCell(
            statisticId: $statistic->id,
            targetType: 'user',
            targetId: 5,
            dimensionKey: 'abc',
            values: ['value' => 3.0, 'result' => ['measure' => 3]],
            dimensions: [
                ['axis' => 'voting_type', 'value_scalar' => 'best'],
                ['axis' => 'platform', 'value_type' => 'platform', 'value_id' => 9],
            ],
        );

        $this->assertInstanceOf(TargetStatistic::class, $cell);
        $this->assertEquals(3.0, $cell->value);
        $this->assertSame('abc', $cell->dimension_key);
        $this->assertCount(2, $cell->dimensions);
    }

    public function test_upsert_cell_is_idempotent_on_the_unique_tuple(): void
    {
        $statistic = Statistic::factory()->create();

        $first = $this->repository->upsertCell(
            $statistic->id, 'user', 5, 'abc',
            ['value' => 1.0, 'result' => ['measure' => 1]],
            [['axis' => 'x', 'value_scalar' => 'a']],
        );

        $second = $this->repository->upsertCell(
            $statistic->id, 'user', 5, 'abc',
            ['value' => 9.0, 'result' => ['measure' => 9]],
            [['axis' => 'x', 'value_scalar' => 'a']],
        );

        // Same logical cell -> updated in place, not duplicated.
        $this->assertSame($first->id, $second->id);
        $this->assertEquals(9.0, $second->fresh()->value);
        $this->assertSame(
            1,
            TargetStatistic::where('statistic_id', $statistic->id)
                ->where('dimension_key', 'abc')->count(),
        );
        // Child rows are rewritten, not accumulated.
        $this->assertCount(1, $second->fresh()->dimensions);
    }

    public function test_delete_stale_cells_removes_only_unlisted_keys(): void
    {
        $statistic = Statistic::factory()->create();

        $this->repository->upsertCell($statistic->id, 'user', 5, 'keep', ['value' => 1.0, 'result' => []]);
        $this->repository->upsertCell($statistic->id, 'user', 5, 'stale', ['value' => 1.0, 'result' => []]);

        $removed = $this->repository->deleteStaleCells($statistic->id, ['keep']);

        $this->assertSame(1, $removed);
        $this->assertNotNull(
            TargetStatistic::where('statistic_id', $statistic->id)->where('dimension_key', 'keep')->first(),
        );
        $this->assertNull(
            TargetStatistic::where('statistic_id', $statistic->id)->where('dimension_key', 'stale')->first(),
        );
    }

    public function test_delete_stale_cells_scopes_to_target(): void
    {
        $statistic = Statistic::factory()->create();

        $this->repository->upsertCell($statistic->id, 'user', 5, 'k', ['value' => 1.0, 'result' => []]);
        $this->repository->upsertCell($statistic->id, 'user', 6, 'k', ['value' => 1.0, 'result' => []]);

        // Prune everything for target 5 (keep nothing); target 6 untouched.
        $this->repository->deleteStaleCells($statistic->id, [], 'user', 5);

        $this->assertNull(
            TargetStatistic::where('statistic_id', $statistic->id)->where('target_id', 5)->first(),
        );
        $this->assertNotNull(
            TargetStatistic::where('statistic_id', $statistic->id)->where('target_id', 6)->first(),
        );
    }

    public function test_cell_value_reads_scalar_or_null(): void
    {
        $statistic = Statistic::factory()->create();
        $this->repository->upsertCell($statistic->id, 'user', 5, 'k', ['value' => 7.0, 'result' => []]);

        $this->assertEquals(7.0, $this->repository->cellValue($statistic->id, 'k', 'user', 5));
        $this->assertNull($this->repository->cellValue($statistic->id, 'missing', 'user', 5));
    }
}
