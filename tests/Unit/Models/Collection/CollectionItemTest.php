<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Collection;

use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Polis\Tests\TestCase;

final class CollectionItemTest extends TestCase
{
    public function test_item(): void
    {
        $model = new CollectionItem;
        $relation = $model->item();

        $this->assertInstanceOf(MorphTo::class, $relation);
    }

    public function test_categories(): void
    {
        $model = new CollectionItem;
        $relation = $model->categories();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertEquals('collection_item_categories', $relation->getTable());
    }

    public function test_collection(): void
    {
        $model = new CollectionItem;
        $relation = $model->collection();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Collection::class, $relation->getRelated());
    }
}
