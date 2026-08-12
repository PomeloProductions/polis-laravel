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
}
