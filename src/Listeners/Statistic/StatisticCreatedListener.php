<?php

declare(strict_types=1);

namespace Polis\Listeners\Statistic;

use Illuminate\Bus\Dispatcher;
use Polis\Contracts\Services\Statistic\StatisticSynchronizationServiceContract;
use Polis\Events\Statistic\StatisticCreatedEvent;
use Polis\Jobs\Statistic\RecountStatisticJob;

class StatisticCreatedListener
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly StatisticSynchronizationServiceContract $synchronizationService
    ) {}

    public function handle(StatisticCreatedEvent $event): void
    {
        $statistic = $event->getStatistic();

        // Create target statistics for the new statistic
        $this->synchronizationService->createTargetStatisticsForStatistic($statistic);

        // Trigger a recount job to process the new target statistics
        $this->dispatcher->dispatch(new RecountStatisticJob($statistic));
    }
}
