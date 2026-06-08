<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Polis\Contracts\Repositories\Statistic\StatisticRepositoryContract;
use Polis\Tests\Fixtures\Controllers\StatisticController;
use Polis\Tests\Fixtures\Models\Statistic as StatisticFixture;

/**
 * Unit coverage for StatisticControllerAbstract — vanilla CRUD shape.
 */
final class StatisticControllerAbstractTest extends ControllerTestCase
{
    public function test_index_forwards_parsed_query_args(): void
    {
        $repo = Mockery::mock(StatisticRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $request = $this->makeIndexRequest('App\\Http\\Core\\Requests\\Statistic\\IndexRequest');

        $repo->shouldReceive('findAll')->once()->andReturn($paginator);

        $this->assertSame($paginator, (new StatisticController($repo))->index($request));
    }

    public function test_show_loads_expand(): void
    {
        $repo = Mockery::mock(StatisticRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Statistic\\ViewRequest');

        $statistic = Mockery::mock(StatisticFixture::class);
        $loaded = Mockery::mock(StatisticFixture::class);
        $statistic->shouldReceive('load')->once()->with([])->andReturn($loaded);

        $this->assertSame($loaded, (new StatisticController($repo))->show($request, $statistic));
    }

    public function test_store_creates_and_returns_201(): void
    {
        $repo = Mockery::mock(StatisticRepositoryContract::class);
        $payload = ['name' => 'Test stat'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Statistic\\StoreRequest', $payload);

        $created = Mockery::mock(StatisticFixture::class);
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')->once()->with($payload)->andReturn($created);

        $response = (new StatisticController($repo))->store($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_update_delegates_to_repository(): void
    {
        $repo = Mockery::mock(StatisticRepositoryContract::class);
        $payload = ['name' => 'Updated'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Statistic\\UpdateRequest', $payload);

        $stat = Mockery::mock(StatisticFixture::class);
        $updated = Mockery::mock(StatisticFixture::class);
        $repo->shouldReceive('update')->once()->with($stat, $payload)->andReturn($updated);

        $this->assertSame($updated, (new StatisticController($repo))->update($request, $stat));
    }

    public function test_destroy_deletes_and_returns_204(): void
    {
        $repo = Mockery::mock(StatisticRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Statistic\\DeleteRequest');

        $stat = Mockery::mock(StatisticFixture::class);
        $repo->shouldReceive('delete')->once()->with($stat);

        $this->assertSame(204, (new StatisticController($repo))->destroy($request, $stat)->getStatusCode());
    }
}
