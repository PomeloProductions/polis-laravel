<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Entity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Payment\PaymentRepositoryContract;
use Polis\Tests\Fixtures\Controllers\Entity\PaymentController;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for Entity\PaymentControllerAbstract.
 *
 * Read-only listing scoped to the entity via owner_* morph filters.
 */
final class PaymentControllerAbstractTest extends ControllerTestCase
{
    public function test_index_appends_owner_filters_for_the_bound_entity(): void
    {
        $repo = Mockery::mock(PaymentRepositoryContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 13;
        $entity->shouldReceive('morphRelationName')->andReturn('users');

        $request = $this->makeIndexRequest('Polis\\Http\\Core\\Requests\\Entity\\Payment\\IndexRequest');
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $repo->shouldReceive('findAll')
            ->once()
            ->with(
                Mockery::on(fn (array $filter) => in_array(['owner_id', '=', 13], $filter, true)
                    && in_array(['owner_type', '=', 'users'], $filter, true)),
                [], [], [], 10, [], 1,
            )
            ->andReturn($paginator);

        $this->assertSame($paginator, (new PaymentController($repo))->index($request, $entity));
    }
}
