<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\Statistic;

use App\Models\Statistic\Statistic;
use Polis\Events\Statistic\StatisticUpdatedEvent;
use Polis\Tests\TestCase;

/**
 * Class StatisticUpdatedEventTest
 */
class StatisticUpdatedEventTest extends TestCase
{
    public function test_get_statistic(): void
    {
        $statistic = new Statistic;
        $event = new StatisticUpdatedEvent($statistic);

        $this->assertSame($statistic, $event->getStatistic());
    }
}
