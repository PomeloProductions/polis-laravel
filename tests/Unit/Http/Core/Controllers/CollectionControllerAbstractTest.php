<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Mockery;
use Polis\Contracts\Repositories\Collection\CollectionRepositoryContract;
use Polis\Tests\Fixtures\Controllers\CollectionController;
use Polis\Tests\Fixtures\Models\Collection as CollectionFixture;

/**
 * Unit coverage for the top-level CollectionControllerAbstract.
 *
 * Note: this is the un-suffixed root controller that handles show/
 * update/destroy on a Collection. Entity-scoped collection listing
 * lives in Entity\CollectionControllerAbstract (covered separately).
 */
final class CollectionControllerAbstractTest extends ControllerTestCase
{
    public function test_show_loads_specified_relations(): void
    {
        $repo = Mockery::mock(CollectionRepositoryContract::class);
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Collection\\ViewRequest', [
            'with' => ['items'],
        ]);

        $collection = Mockery::mock(CollectionFixture::class);
        $loaded = Mockery::mock(CollectionFixture::class);
        $collection->shouldReceive('load')->once()->with(['items'])->andReturn($loaded);

        $this->assertSame($loaded, (new CollectionController($repo))->show($request, $collection));
    }

    public function test_update_delegates_to_repository(): void
    {
        $repo = Mockery::mock(CollectionRepositoryContract::class);
        $payload = ['name' => 'Renamed Collection'];

        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Collection\\UpdateRequest', $payload);
        $collection = Mockery::mock(CollectionFixture::class);
        $updated = Mockery::mock(CollectionFixture::class);
        $repo->shouldReceive('update')->once()->with($collection, $payload)->andReturn($updated);

        $this->assertSame($updated, (new CollectionController($repo))->update($request, $collection));
    }

    public function test_destroy_deletes_and_returns_204(): void
    {
        $repo = Mockery::mock(CollectionRepositoryContract::class);
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Collection\\DeleteRequest');
        $collection = Mockery::mock(CollectionFixture::class);
        $repo->shouldReceive('delete')->once()->with($collection);

        $response = (new CollectionController($repo))->destroy($request, $collection);

        $this->assertSame(204, $response->getStatusCode());
    }
}
