<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Polis\Contracts\Repositories\ResourceRepositoryContract;
use Polis\Tests\Fixtures\Controllers\ResourceController;

/**
 * Unit coverage for ResourceControllerAbstract.
 *
 * Single-action controller (index only) — pin the trait-helper plumbing
 * forwards to the repository correctly.
 */
final class ResourceControllerAbstractTest extends ControllerTestCase
{
    public function test_index_forwards_parsed_query_args(): void
    {
        $repo = Mockery::mock(ResourceRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest(
            'App\\Http\\Core\\Requests\\Resource\\IndexRequest',
            ['limit' => 100, 'page' => 5],
        );

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 100, [], 5)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new ResourceController($repo))->index($request));
    }
}
