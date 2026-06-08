<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Collection;

use App\Models\Collection\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Polis\Contracts\Repositories\Collection\CollectionItemRepositoryContract;
use Polis\Repositories\Collection\CollectionRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for CollectionRepository::update — the override that reorders
 * collection items based on a `collection_item_order` array, and gracefully
 * skips items that no longer exist (catching ModelNotFoundException).
 */
final class CollectionRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! class_exists(Collection::class, false)) {
            eval('namespace App\\Models\\Collection; class Collection extends \\Polis\\Models\\BaseModelAbstract {}');
        }
    }

    public function test_update_without_order_does_not_call_item_repo(): void
    {
        $modelMock = Mockery::mock(Collection::class);
        $modelMock->shouldReceive('update')->once()->andReturn(true);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);

        $itemRepo = Mockery::mock(CollectionItemRepositoryContract::class);
        $itemRepo->shouldNotReceive('update');

        $repo = new CollectionRepository($modelMock, $this->getGenericLogMock(), $itemRepo);
        $repo->update($modelMock, ['name' => 'updated']);
    }

    public function test_update_with_order_updates_each_item_with_index(): void
    {
        $itemMock1 = new Collection;
        $itemMock1->id = 100;
        $itemMock2 = new Collection;
        $itemMock2->id = 101;

        $modelMock = Mockery::mock(Collection::class);
        $modelMock->shouldReceive('update')->once()->andReturn(true);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')
            ->with('collectionItems')->andReturn(null); // just for the side-effect read
        $modelMock->shouldReceive('getAttribute')->andReturn(1);

        $itemRepo = Mockery::mock(CollectionItemRepositoryContract::class);
        $itemRepo->shouldReceive('findOrFail')->once()->with(100)->andReturn($itemMock1);
        $itemRepo->shouldReceive('update')->once()->with($itemMock1, ['order' => 0]);
        $itemRepo->shouldReceive('findOrFail')->once()->with(101)->andReturn($itemMock2);
        $itemRepo->shouldReceive('update')->once()->with($itemMock2, ['order' => 1]);

        $repo = new CollectionRepository($modelMock, $this->getGenericLogMock(), $itemRepo);
        $repo->update($modelMock, ['collection_item_order' => [100, 101]]);
    }

    public function test_update_swallows_model_not_found_for_missing_items(): void
    {
        $modelMock = Mockery::mock(Collection::class);
        $modelMock->shouldReceive('update')->once()->andReturn(true);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->with('collectionItems')->andReturn(null);
        $modelMock->shouldReceive('getAttribute')->andReturn(1);

        $itemRepo = Mockery::mock(CollectionItemRepositoryContract::class);
        $itemRepo->shouldReceive('findOrFail')->once()->with(999)
            ->andThrow(new ModelNotFoundException);
        $itemRepo->shouldNotReceive('update');

        $repo = new CollectionRepository($modelMock, $this->getGenericLogMock(), $itemRepo);
        // Should not throw — exception is swallowed
        $repo->update($modelMock, ['collection_item_order' => [999]]);

        $this->assertTrue(true);
    }
}
