<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\Statistic\StatisticFilter;
use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polis\Tests\TestCase;

class StatisticTest extends TestCase
{
    public function test_statistic_filters_relation(): void
    {
        $model = new Statistic;
        $relation = $model->statisticFilters();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(StatisticFilter::class, get_class($relation->getRelated()));
    }

    public function test_target_statistics_relation(): void
    {
        $model = new Statistic;
        $relation = $model->targetStatistics();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(TargetStatistic::class, get_class($relation->getRelated()));
    }
}
