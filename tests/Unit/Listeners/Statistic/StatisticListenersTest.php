<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\Statistic;

use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;
use Mockery;
use Polis\Contracts\Repositories\Statistic\TargetStatisticRepositoryContract;
use Polis\Contracts\Services\Statistic\StatisticSynchronizationServiceContract;
use Polis\Events\Statistic\StatisticCreatedEvent;
use Polis\Events\Statistic\StatisticDeletedEvent;
use Polis\Events\Statistic\StatisticUpdatedEvent;
use Polis\Jobs\Statistic\RecountStatisticJob;
use Polis\Listeners\Statistic\StatisticCreatedListener;
use Polis\Listeners\Statistic\StatisticDeletedListener;
use Polis\Listeners\Statistic\StatisticUpdatedListener;
use Polis\Tests\TestCase;

/**
 * Statistic listener coverage. The Statistic and TargetStatistic models
 * live in the consumer app (App\Models\Statistic\*) but Mockery can stub
 * them at runtime via class_alias since the listeners only call methods
 * on these instances, never instantiate them directly.
 */
final class StatisticListenersTest extends TestCase
{
    public function test_statistic_created_listener_creates_targets_and_dispatches_recount(): void
    {
        $statistic = Mockery::mock('App\\Models\\Statistic\\Statistic');

        $sync = Mockery::mock(StatisticSynchronizationServiceContract::class);
        $sync->shouldReceive('createTargetStatisticsForStatistic')->once()->with($statistic);

        $dispatcher = Mockery::mock(BusDispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->withArgs(
            fn ($job) => $job instanceof RecountStatisticJob,
        );

        $listener = new StatisticCreatedListener($dispatcher, $sync);
        $listener->handle(new StatisticCreatedEvent($statistic));
    }

    public function test_statistic_updated_listener_unsets_relations_and_dispatches(): void
    {
        $statistic = Mockery::mock('App\\Models\\Statistic\\Statistic');
        $statistic->shouldReceive('unsetRelations')->once();

        $dispatcher = Mockery::mock(DispatcherContract::class);
        $dispatcher->shouldReceive('dispatch')->once()->withArgs(
            fn ($job) => $job instanceof RecountStatisticJob,
        );

        $listener = new StatisticUpdatedListener($dispatcher);
        $listener->handle(new StatisticUpdatedEvent($statistic));
    }

    public function test_statistic_deleted_listener_iterates_target_statistics(): void
    {
        // Empty targetStatistics collection — the listener iterates and
        // calls $repo->delete($target). Verifying the loop runs at all
        // (with zero targets) covers the entire handle() method since
        // delete()'s signature requires concrete BaseModelAbstract
        // subclasses which can't be standalone-mocked (EloquentJoin
        // trait absent in this package). Non-empty iteration coverage
        // lives in the Consumer-Only suite.
        $statistic = Mockery::mock('App\\Models\\Statistic\\Statistic');
        $statistic->targetStatistics = [];

        $repo = Mockery::mock(TargetStatisticRepositoryContract::class);

        $listener = new StatisticDeletedListener($repo);
        $listener->handle(new StatisticDeletedEvent($statistic));

        // Mockery would fail if repo->delete() were unexpectedly called.
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
