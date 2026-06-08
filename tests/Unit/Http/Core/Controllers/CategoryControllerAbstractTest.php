<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Polis\Contracts\Repositories\CategoryRepositoryContract;
use Polis\Tests\Fixtures\Controllers\CategoryController;
use Polis\Tests\Fixtures\Models\Category as CategoryFixture;

/**
 * Unit coverage for CategoryControllerAbstract.
 *
 * The canonical five-method CRUD shape: index forwards parsed
 * filter/search/order/expand/limit/page to the repository's findAll;
 * show loads expand on the bound model; store/update/destroy delegate
 * straight to the repository.
 */
final class CategoryControllerAbstractTest extends ControllerTestCase
{
    public function test_index_delegates_to_repository_findAll_with_parsed_query_args(): void
    {
        $repo = Mockery::mock(CategoryRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest(
            'App\\Http\\Core\\Requests\\Category\\IndexRequest',
            [
                'cleaned_filter' => [['name', '=', 'foo']],
                'cleaned_search' => [['name', 'like', '%bar%']],
                'order' => ['created_at' => 'desc'],
                'with' => ['articles'],
                'limit' => 25,
                'page' => 3,
            ],
        );

        $repo->shouldReceive('findAll')
            ->once()
            ->with(
                [['name', '=', 'foo']],
                [['name', 'like', '%bar%']],
                ['created_at' => 'desc'],
                ['articles'],
                25,
                [],
                3,
            )
            ->andReturn($paginator);

        $controller = new CategoryController($repo);

        $this->assertSame($paginator, $controller->index($request));
    }

    public function test_index_uses_defaults_when_request_inputs_are_unset(): void
    {
        $repo = Mockery::mock(CategoryRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest('App\\Http\\Core\\Requests\\Category\\IndexRequest');

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 10, [], 1)
            ->andReturn($paginator);

        $controller = new CategoryController($repo);

        $this->assertSame($paginator, $controller->index($request));
    }

    public function test_show_loads_expand_with_specified_relations(): void
    {
        $repo = Mockery::mock(CategoryRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Category\\ViewRequest', [
            'with' => ['articles'],
        ]);

        $model = Mockery::mock(CategoryFixture::class);
        $loaded = Mockery::mock(CategoryFixture::class);
        $model->shouldReceive('load')->once()->with(['articles'])->andReturn($loaded);

        $controller = new CategoryController($repo);

        $this->assertSame($loaded, $controller->show($request, $model));
    }

    public function test_store_creates_via_repository_and_returns_201(): void
    {
        $repo = Mockery::mock(CategoryRepositoryContract::class);
        $payload = ['name' => 'New Category'];

        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Category\\StoreRequest', $payload);

        $created = Mockery::mock(CategoryFixture::class);
        // response($model, 201) renders the model via toJson()
        $created->shouldReceive('toJson')->andReturn(json_encode(['id' => 7] + $payload));
        $repo->shouldReceive('create')->once()->with($payload)->andReturn($created);

        $controller = new CategoryController($repo);

        $response = $controller->store($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($created, $response->getOriginalContent());
    }

    public function test_update_delegates_to_repository_with_request_payload(): void
    {
        $repo = Mockery::mock(CategoryRepositoryContract::class);
        $payload = ['name' => 'Renamed'];

        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Category\\UpdateRequest', $payload);

        $model = Mockery::mock(CategoryFixture::class);
        $updated = Mockery::mock(CategoryFixture::class);
        $repo->shouldReceive('update')->once()->with($model, $payload)->andReturn($updated);

        $controller = new CategoryController($repo);

        $this->assertSame($updated, $controller->update($request, $model));
    }

    public function test_destroy_deletes_via_repository_and_returns_204_no_content(): void
    {
        $repo = Mockery::mock(CategoryRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Category\\DeleteRequest');

        $model = Mockery::mock(CategoryFixture::class);
        $repo->shouldReceive('delete')->once()->with($model);

        $controller = new CategoryController($repo);

        $response = $controller->destroy($request, $model);

        $this->assertSame(204, $response->getStatusCode());
    }
}
