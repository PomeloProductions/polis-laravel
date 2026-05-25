<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\Statistic\StatisticFilter;
use Polis\Repositories\Statistic\StatisticFilterRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class StatisticFilterRepositoryTest
 */
class StatisticFilterRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var StatisticFilterRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();

        $this->repository = new StatisticFilterRepository(
            new StatisticFilter,
            $this->getGenericLogMock()
        );
    }

    public function test_find_all_returns_collection()
    {
        StatisticFilter::factory()->count(5)->create();

        $models = $this->repository->findAll();

        $this->assertCount(5, $models);
    }

    public function test_find_returns_model()
    {
        $model = StatisticFilter::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);

        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_throws_exception()
    {
        StatisticFilter::factory()->create(['id' => 2]);

        $this->expectException(\Exception::class);

        $this->repository->findOrFail(1);
    }

    public function test_create_success()
    {
        /** @var Statistic $statistic */
        $statistic = Statistic::factory()->create();

        /** @var StatisticFilter $statisticFilter */
        $statisticFilter = $this->repository->create([
            'field' => 'active',
            'operator' => '=',
            'value' => '1',
        ], $statistic);

        $this->assertCount(1, StatisticFilter::all());
        $this->assertEquals($statisticFilter->statistic_id, $statistic->id);
        $this->assertEquals('active', $statisticFilter->field);
        $this->assertEquals('=', $statisticFilter->operator);
        $this->assertEquals('1', $statisticFilter->value);
    }

    public function test_update_success()
    {
        /** @var StatisticFilter $statisticFilter */
        $statisticFilter = StatisticFilter::factory()->create([
            'field' => 'active',
        ]);

        /** @var StatisticFilter $result */
        $result = $this->repository->update($statisticFilter, [
            'field' => 'type',
        ]);

        $this->assertEquals('type', $result->field);
    }

    public function test_delete_success()
    {
        $model = StatisticFilter::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(StatisticFilter::find($model->id));
    }
}
