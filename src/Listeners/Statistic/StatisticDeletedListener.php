<?php

declare(strict_types=1);

namespace Polis\Listeners\Statistic;

use Polis\Contracts\Repositories\Statistic\TargetStatisticRepositoryContract;
use Polis\Events\Statistic\StatisticDeletedEvent;

class StatisticDeletedListener
{
    public function __construct(
        private readonly TargetStatisticRepositoryContract $targetStatisticRepository
    ) {}

    public function handle(StatisticDeletedEvent $event): void
    {
        $statistic = $event->getStatistic();

        // Delete all target statistics related to this statistic
        foreach ($statistic->targetStatistics as $targetStatistic) {
            $this->targetStatisticRepository->delete($targetStatistic);
        }
    }
}
