<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\Statistic\StatisticFilter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Polis\Events\Statistic\StatisticCreatedEvent;
use Polis\Events\Statistic\StatisticDeletedEvent;
use Polis\Events\Statistic\StatisticUpdatedEvent;
use Polis\Repositories\Statistic\StatisticFilterRepository;
use Polis\Repositories\Statistic\StatisticRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class StatisticRepositoryTest
 */
class StatisticRepositoryTest extends TestCase
{
    use DatabaseSetupTrait;

    /**
     * @var StatisticRepository
     */
    private $repository;

    /**
     * @var StatisticFilterRepository
     */
    private $statisticFilterRepository;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->dispatcher = $this->createMock(Dispatcher::class);
        $this->statisticFilterRepository = app(StatisticFilterRepository::class);
        $this->repository = new StatisticRepository(
            app(Statistic::class),
            $this->getGenericLogMock(),
            $this->statisticFilterRepository,
            $this->dispatcher
        );
    }

    public function test_find_all_returns_collection()
    {
        foreach (Statistic::all() as $model) {
            $model->delete();
        }
        Statistic::factory()->count(5)->create();

        $models = $this->repository->findAll();

        $this->assertCount(5, $models);
    }

    public function test_find_all_with_filter_returns_collection()
    {
        foreach (Statistic::all() as $model) {
            $model->delete();
        }
        Statistic::factory()->create(['id' => 1]);
        Statistic::factory()->count(4)->create();

        $models = $this->repository->findAll(['id' => 1]);

        $this->assertCount(1, $models);
    }

    public function test_find_all_for_model_returns_only_statistics_for_that_model()
    {
        foreach (Statistic::all() as $model) {
            $model->delete();
        }

        // Create statistics for different models
        Statistic::factory()->count(3)->create(['model' => 'article']);
        Statistic::factory()->count(2)->create(['model' => 'user']);
        Statistic::factory()->count(1)->create(['model' => 'organization']);

        $articleStatistics = $this->repository->findAllForModel('article');
        $userStatistics = $this->repository->findAllForModel('user');
        $organizationStatistics = $this->repository->findAllForModel('organization');

        $this->assertCount(3, $articleStatistics);
        $this->assertCount(2, $userStatistics);
        $this->assertCount(1, $organizationStatistics);

        // Verify all returned statistics have the correct model
        foreach ($articleStatistics as $statistic) {
            $this->assertEquals('article', $statistic->model);
        }
        foreach ($userStatistics as $statistic) {
            $this->assertEquals('user', $statistic->model);
        }
    }

    public function test_find_all_for_model_returns_empty_collection_when_no_statistics_exist()
    {
        foreach (Statistic::all() as $model) {
            $model->delete();
        }

        $statistics = $this->repository->findAllForModel('nonexistent');

        $this->assertCount(0, $statistics);
        $this->assertInstanceOf(Collection::class, $statistics);
    }

    public function test_find_returns_model()
    {
        foreach (Statistic::all() as $model) {
            $model->delete();
        }
        $model = Statistic::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);

        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_throws_exception()
    {
        foreach (Statistic::all() as $model) {
            $model->delete();
        }
        Statistic::factory()->create(['id' => 35]);

        $this->expectException(\Exception::class);

        $this->repository->findOrFail(1);
    }

    public function test_create_statistic_with_filters()
    {
        $data = [
            'name' => 'Test Statistic',
            'model' => 'User',
            'relation' => 'contacts',
            'public' => true,
            'statistic_filters' => [
                [
                    'field' => 'active',
                    'operator' => '=',
                    'value' => '1',
                ],
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'character',
                ],
            ],
        ];

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof StatisticCreatedEvent;
            }));

        $statistic = $this->repository->create($data);

        $this->assertInstanceOf(Statistic::class, $statistic);
        $this->assertEquals('Test Statistic', $statistic->name);
        $this->assertEquals('User', $statistic->model);
        $this->assertEquals('contacts', $statistic->relation);
        $this->assertTrue($statistic->public);

        $this->assertCount(2, $statistic->statisticFilters);
        $this->assertInstanceOf(Collection::class, $statistic->statisticFilters);

        $filter1 = $statistic->statisticFilters->first();
        $this->assertInstanceOf(StatisticFilter::class, $filter1);
        $this->assertEquals('active', $filter1->field);
        $this->assertEquals('=', $filter1->operator);
        $this->assertEquals('1', $filter1->value);

        $filter2 = $statistic->statisticFilters->last();
        $this->assertInstanceOf(StatisticFilter::class, $filter2);
        $this->assertEquals('type', $filter2->field);
        $this->assertEquals('=', $filter2->operator);
        $this->assertEquals('character', $filter2->value);
    }

    public function test_update_statistic_with_filters()
    {
        $statistic = Statistic::factory()->create();
        $filter1 = StatisticFilter::factory()->create([
            'statistic_id' => $statistic->id,
            'field' => 'active',
            'operator' => '=',
            'value' => '0',
        ]);
        $filter2 = StatisticFilter::factory()->create([
            'statistic_id' => $statistic->id,
            'field' => 'type',
            'operator' => '=',
            'value' => 'user',
        ]);

        $data = [
            'name' => 'Updated Statistic',
            'public' => false,
            'statistic_filters' => [
                [
                    'id' => $filter1->id,
                    'field' => 'active',
                    'operator' => '=',
                    'value' => '1',
                ],
                [
                    'id' => $filter2->id,
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'character',
                ],
            ],
        ];

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof StatisticUpdatedEvent;
            }));

        $updatedStatistic = $this->repository->update($statistic, $data);

        $this->assertInstanceOf(Statistic::class, $updatedStatistic);
        $this->assertEquals('Updated Statistic', $updatedStatistic->name);
        $this->assertFalse($updatedStatistic->public);

        $this->assertCount(2, $updatedStatistic->statisticFilters);
        $this->assertInstanceOf(Collection::class, $updatedStatistic->statisticFilters);

        $filter1 = $updatedStatistic->statisticFilters->first();
        $this->assertInstanceOf(StatisticFilter::class, $filter1);
        $this->assertEquals('active', $filter1->field);
        $this->assertEquals('=', $filter1->operator);
        $this->assertEquals('1', $filter1->value);

        $filter2 = $updatedStatistic->statisticFilters->last();
        $this->assertInstanceOf(StatisticFilter::class, $filter2);
        $this->assertEquals('type', $filter2->field);
        $this->assertEquals('=', $filter2->operator);
        $this->assertEquals('character', $filter2->value);
    }

    public function test_delete_success()
    {
        $statistic = Statistic::factory()->create();

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof StatisticDeletedEvent;
            }));

        $this->repository->delete($statistic);

        $this->assertNull(Statistic::find($statistic->id));
    }
}
