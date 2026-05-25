<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\Statistic;

use App\Models\Statistic\Statistic;
use Illuminate\Bus\Dispatcher;
use Mockery;
use Mockery\MockInterface;
use Polis\Events\Statistic\StatisticUpdatedEvent;
use Polis\Jobs\Statistic\RecountStatisticJob;
use Polis\Listeners\Statistic\StatisticUpdatedListener;
use Polis\Tests\TestCase;

/**
 * Class StatisticUpdatedListenerTest
 */
class StatisticUpdatedListenerTest extends TestCase
{
    public function test_handle(): void
    {
        $statistic = new Statistic;
        $statistic->id = 234;

        $event = new StatisticUpdatedEvent($statistic);

        /** @var Dispatcher|MockInterface $dispatcher */
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->with(Mockery::type(RecountStatisticJob::class))
            ->once();

        $listener = new StatisticUpdatedListener($dispatcher);
        $listener->handle($event);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
