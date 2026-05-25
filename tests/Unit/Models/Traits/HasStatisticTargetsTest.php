<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Traits;

use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Polis\Models\Traits\HasStatisticTargets;
use Polis\Tests\TestCase;

/**
 * Class HasStatisticTargetsTest
 */
class HasStatisticTargetsTest extends TestCase
{
    public function test_target_statistics_relationship()
    {
        $model = new class extends Model
        {
            use HasStatisticTargets;
        };

        $relation = $model->targetStatistics();

        $this->assertInstanceOf(MorphMany::class, $relation);
        $this->assertEquals('target_type', $relation->getMorphType());
        $this->assertEquals('target_id', $relation->getForeignKeyName());
        $this->assertEquals(TargetStatistic::class, get_class($relation->getRelated()));
    }
}
