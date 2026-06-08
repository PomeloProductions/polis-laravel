<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Collection;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Repositories\Collection\CollectionItemRepositoryContract;
use Polis\Tests\Fixtures\Controllers\Collection\CollectionItemController;
use Polis\Tests\Fixtures\Models\Collection as CollectionFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for Collection\CollectionItemControllerAbstract.
 *
 * Collection-scoped index() + store(). The sibling root
 * CollectionItemControllerAbstract handles show() + destroy(); covered
 * separately.
 */
final class CollectionItemControllerAbstractTest extends ControllerTestCase
{
    public function test_index_scopes_findAll_to_parent_collection(): void
    {
        $repo = Mockery::mock(CollectionItemRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $request = $this->makeIndexRequest(
            'App\\Http\\Core\\Requests\\Collection\\CollectionItem\\IndexRequest',
        );
        $collection = Mockery::mock(CollectionFixture::class);

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 10, [$collection], 1)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new CollectionItemController($repo))->index($request, $collection));
    }

    public function test_store_creates_under_collection_and_returns_201(): void
    {
        $repo = Mockery::mock(CollectionItemRepositoryContract::class);
        $payload = ['name' => 'Item'];
        $request = $this->makeRequest(
            'App\\Http\\Core\\Requests\\Collection\\CollectionItem\\StoreRequest',
            $payload,
        );
        $collection = Mockery::mock(CollectionFixture::class);

        $created = Mockery::mock();
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')
            ->once()
            ->with($payload, $collection)
            ->andReturn($created);

        $response = (new CollectionItemController($repo))->store($request, $collection);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }
}
