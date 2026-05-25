<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\Statistic;

use App\Models\Statistic\Statistic;
use Polis\Events\Statistic\StatisticDeletedEvent;
use Polis\Tests\TestCase;

class StatisticDeletedEventTest extends TestCase
{
    public function test_get_statistic(): void
    {
        $statistic = new Statistic;
        $event = new StatisticDeletedEvent($statistic);

        $this->assertSame($statistic, $event->getStatistic());
    }
}
