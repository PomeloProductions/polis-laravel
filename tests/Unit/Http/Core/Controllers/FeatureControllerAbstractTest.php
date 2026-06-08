<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Polis\Contracts\Repositories\FeatureRepositoryContract;
use Polis\Tests\Fixtures\Controllers\FeatureController;
use Polis\Tests\Fixtures\Models\Feature as FeatureFixture;

/**
 * Unit coverage for FeatureControllerAbstract.
 *
 * Read-only controller (index + show only) over a FeatureRepository.
 */
final class FeatureControllerAbstractTest extends ControllerTestCase
{
    public function test_index_forwards_parsed_query_args_to_repository(): void
    {
        $repo = Mockery::mock(FeatureRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest(
            'App\\Http\\Core\\Requests\\Feature\\IndexRequest',
            ['limit' => 50],
        );

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 50, [], 1)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new FeatureController($repo))->index($request));
    }

    public function test_show_loads_expand(): void
    {
        $repo = Mockery::mock(FeatureRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Feature\\ViewRequest');

        $feature = Mockery::mock(FeatureFixture::class);
        $loaded = Mockery::mock(FeatureFixture::class);
        $feature->shouldReceive('load')->once()->with([])->andReturn($loaded);

        $this->assertSame($loaded, (new FeatureController($repo))->show($request, $feature));
    }
}
