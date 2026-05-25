<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\Statistic\StatisticFilter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polis\Tests\TestCase;

class StatisticFilterTest extends TestCase
{
    public function test_statistic_relation(): void
    {
        $model = new StatisticFilter;
        $relation = $model->statistic();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(Statistic::class, get_class($relation->getRelated()));
    }
}
