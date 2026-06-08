<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Statistic;

use App\Models\Statistic\Statistic;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Polis\Events\Statistic\StatisticCreatedEvent;
use Polis\Events\Statistic\StatisticDeletedEvent;
use Polis\Events\Statistic\StatisticUpdatedEvent;
use Polis\Repositories\Statistic\StatisticFilterRepository;
use Polis\Repositories\Statistic\StatisticRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for StatisticRepository — event dispatching on
 * create/update/delete, the filter-sync side effect, and the
 * findAllForModel helper.
 */
final class StatisticRepositoryTest extends TestCase
{
    private function buildModelMock(int $id = 7)
    {
        $mock = Mockery::mock(Statistic::class);
        $mock->shouldReceive('setAttribute');
        $mock->shouldReceive('getAttribute')->andReturn($id);
        $mock->wasRecentlyCreated = true;

        return $mock;
    }

    public function test_model_returns_concrete_class_name(): void
    {
        $modelMock = $this->buildModelMock();

        $filterRepo = Mockery::mock(StatisticFilterRepository::class);
        $dispatcher = Mockery::mock(Dispatcher::class);

        $repo = new StatisticRepository($modelMock, $this->getGenericLogMock(), $filterRepo, $dispatcher);
        $this->assertSame(Statistic::class, $repo->model());
    }

    public function test_create_dispatches_event_and_skips_filter_sync_when_no_filters(): void
    {
        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);

        $filterRepo = Mockery::mock(StatisticFilterRepository::class);
        $filterRepo->shouldNotReceive('create');

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof StatisticCreatedEvent);

        $repo = new StatisticRepository($modelMock, $this->getGenericLogMock(), $filterRepo, $dispatcher);
        $repo->create();
    }

    public function test_create_with_filters_syncs_them(): void
    {
        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);

        $filterRepo = Mockery::mock(StatisticFilterRepository::class);
        // syncChildModels calls create() with empty existing children and a parent
        $filterRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data, $parent) use ($modelMock) {
                return $data === ['field' => 'value'] && $parent === $modelMock;
            });

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once();

        $repo = new StatisticRepository($modelMock, $this->getGenericLogMock(), $filterRepo, $dispatcher);
        $repo->create(['statistic_filters' => [['field' => 'value']]]);
    }

    public function test_update_dispatches_event_after_persist(): void
    {
        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('update')->once()->andReturn(true);

        $filterRepo = Mockery::mock(StatisticFilterRepository::class);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof StatisticUpdatedEvent);

        $repo = new StatisticRepository($modelMock, $this->getGenericLogMock(), $filterRepo, $dispatcher);
        $repo->update($modelMock, ['name' => 'updated']);
    }

    public function test_delete_dispatches_deleted_event(): void
    {
        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('delete')->once()->andReturn(true);

        $filterRepo = Mockery::mock(StatisticFilterRepository::class);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof StatisticDeletedEvent);

        $repo = new StatisticRepository($modelMock, $this->getGenericLogMock(), $filterRepo, $dispatcher);
        $repo->delete($modelMock);
    }

    public function test_find_all_for_model_filters_by_model_column(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('where')->once()->with('model', 'article')->andReturnSelf();
        $expected = new \Illuminate\Support\Collection;
        $query->shouldReceive('get')->once()->andReturn($expected);

        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('newQuery')->once()->andReturn($query);

        $filterRepo = Mockery::mock(StatisticFilterRepository::class);
        $dispatcher = Mockery::mock(Dispatcher::class);

        $repo = new StatisticRepository($modelMock, $this->getGenericLogMock(), $filterRepo, $dispatcher);
        $this->assertSame($expected, $repo->findAllForModel('article'));
    }
}
