<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use Polis\Tests\TestCase;

/**
 * Class TargetStatisticTest
 */
class TargetStatisticTest extends TestCase
{
    public function test_target_relationship()
    {
        $model = new TargetStatistic;
        $relation = $model->target();

        $this->assertEquals('target_type', $relation->getMorphType());
        $this->assertEquals('target_id', $relation->getForeignKeyName());
    }

    public function test_statistic_relationship()
    {
        $model = new TargetStatistic;
        $relation = $model->statistic();

        $this->assertEquals('statistic_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Statistic::class, $relation->getRelated());
    }
}
