<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Collection;

use App\Models\Collection\Collection;
use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Polis\Tests\TestCase;

final class CollectionTest extends TestCase
{
    public function test_collection_items(): void
    {
        $model = new Collection;
        $relation = $model->collectionItems();

        $this->assertEquals('collections.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('collection_items.collection_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_owner(): void
    {
        $model = new Collection;
        $relation = $model->owner();

        $this->assertEquals('collections.owner_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('owner_type', $relation->getMorphType());
    }

    public function test_target_statistics_relation(): void
    {
        $model = new Collection;
        $relation = $model->targetStatistics();

        $this->assertInstanceOf(MorphMany::class, $relation);
        $this->assertEquals('target_type', $relation->getMorphType());
        $this->assertEquals('target_id', $relation->getForeignKeyName());
        $this->assertEquals(TargetStatistic::class, get_class($relation->getRelated()));
    }
}
