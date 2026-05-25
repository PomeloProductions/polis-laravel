<?php

declare(strict_types=1);

namespace Polis\Listeners\Statistic;

use Illuminate\Contracts\Bus\Dispatcher;
use Polis\Events\Statistic\StatisticUpdatedEvent;
use Polis\Jobs\Statistic\RecountStatisticJob;

class StatisticUpdatedListener
{
    public function __construct(
        private readonly Dispatcher $dispatcher
    ) {}

    public function handle(StatisticUpdatedEvent $event)
    {
        $statistic = $event->getStatistic();
        $statistic->unsetRelations();
        $this->dispatcher->dispatch(new RecountStatisticJob($statistic));
    }
}
