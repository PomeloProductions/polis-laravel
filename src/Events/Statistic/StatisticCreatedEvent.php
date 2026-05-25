<?php

declare(strict_types=1);

namespace Polis\Events\Statistic;

use App\Models\Statistic\Statistic;

class StatisticCreatedEvent
{
    public function __construct(
        private readonly Statistic $statistic
    ) {}

    public function getStatistic(): Statistic
    {
        return $this->statistic;
    }
}
