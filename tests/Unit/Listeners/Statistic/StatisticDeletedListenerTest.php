<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\MockInterface;
use Polis\Contracts\Repositories\Statistic\TargetStatisticRepositoryContract;
use Polis\Events\Statistic\StatisticDeletedEvent;
use Polis\Listeners\Statistic\StatisticDeletedListener;
use Polis\Tests\TestCase;

class StatisticDeletedListenerTest extends TestCase
{
    public function test_handle_deletes_all_target_statistics(): void
    {
        // Create a statistic with some target statistics
        $statistic = new Statistic;
        $statistic->id = 1;

        $targetStatistics = new Collection([
            new TargetStatistic(['id' => 1]),
            new TargetStatistic(['id' => 2]),
            new TargetStatistic(['id' => 3]),
        ]);

        $statistic->setRelation('targetStatistics', $targetStatistics);

        $event = new StatisticDeletedEvent($statistic);

        /** @var TargetStatisticRepositoryContract|MockInterface $targetStatisticRepository */
        $targetStatisticRepository = Mockery::mock(TargetStatisticRepositoryContract::class);
        $targetStatisticRepository->shouldReceive('delete')
            ->times(3)
            ->with(Mockery::type(TargetStatistic::class));

        $listener = new StatisticDeletedListener($targetStatisticRepository);
        $listener->handle($event);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
