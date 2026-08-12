<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Polis\Contracts\Repositories\RoleRepositoryContract;
use Polis\Tests\Fixtures\Controllers\RoleController;

/**
 * Unit coverage for RoleControllerAbstract.
 *
 * Single-action controller (index only).
 */
final class RoleControllerAbstractTest extends ControllerTestCase
{
    public function test_index_forwards_parsed_query_args(): void
    {
        $repo = Mockery::mock(RoleRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $request = $this->makeIndexRequest('Polis\\Http\\Core\\Requests\\Role\\IndexRequest', [
            'order' => ['name' => 'asc'],
        ]);

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], ['name' => 'asc'], [], 10, [], 1)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new RoleController($repo))->index($request));
    }
}
