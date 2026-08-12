<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Mockery;
use Polis\Contracts\Repositories\Collection\CollectionItemRepositoryContract;
use Polis\Tests\Fixtures\Controllers\CollectionItemController;
use Polis\Tests\Fixtures\Models\CollectionItem as CollectionItemFixture;

/**
 * Unit coverage for the top-level CollectionItemControllerAbstract.
 *
 * This controller only exposes show() + destroy(). The corresponding
 * index() + store() live in Collection\CollectionItemControllerAbstract
 * (a sibling, covered separately).
 */
final class CollectionItemControllerAbstractTest extends ControllerTestCase
{
    public function test_show_loads_specified_relations(): void
    {
        $repo = Mockery::mock(CollectionItemRepositoryContract::class);
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\CollectionItem\\ViewRequest', [
            'with' => ['collection'],
        ]);

        $item = Mockery::mock(CollectionItemFixture::class);
        $loaded = Mockery::mock(CollectionItemFixture::class);
        $item->shouldReceive('load')->once()->with(['collection'])->andReturn($loaded);

        $this->assertSame($loaded, (new CollectionItemController($repo))->show($request, $item));
    }

    public function test_destroy_deletes_and_returns_204(): void
    {
        $repo = Mockery::mock(CollectionItemRepositoryContract::class);
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\CollectionItem\\DeleteRequest');

        $item = Mockery::mock(CollectionItemFixture::class);
        $repo->shouldReceive('delete')->once()->with($item);

        $response = (new CollectionItemController($repo))->destroy($request, $item);

        $this->assertSame(204, $response->getStatusCode());
    }
}
