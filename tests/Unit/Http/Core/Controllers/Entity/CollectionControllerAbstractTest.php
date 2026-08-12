<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Entity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Collection\CollectionRepositoryContract;
use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Controllers\Entity\CollectionController;
use Polis\Tests\Fixtures\Models\Role;
use Polis\Tests\Fixtures\Models\User as UserFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for Entity\CollectionControllerAbstract.
 *
 * index() narrows results to the entity's collections, plus optionally
 * to is_public=1 when the logged-in user is not a MANAGER of the entity.
 * This is the key authorization wrinkle.
 */
final class EntityCollectionControllerAbstractTest extends ControllerTestCase
{
    public function test_index_appends_is_public_filter_for_unauthenticated_viewer(): void
    {
        $repo = Mockery::mock(CollectionRepositoryContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 5;
        $entity->shouldReceive('morphRelationName')->andReturn('users');
        $entity->shouldReceive('canUserManageEntity')->never();

        Auth::shouldReceive('user')->once()->andReturn(null);

        $request = $this->makeIndexRequest('Polis\\Http\\Core\\Requests\\Entity\\Collection\\IndexRequest');
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $repo->shouldReceive('findAll')
            ->once()
            ->with(
                Mockery::on(fn (array $filter) => in_array(['is_public', '=', '1'], $filter, true)),
                [], [], [], 10, [], 1,
            )
            ->andReturn($paginator);

        $this->assertSame($paginator, (new CollectionController($repo))->index($request, $entity));
    }

    public function test_index_omits_is_public_filter_for_entity_manager(): void
    {
        $repo = Mockery::mock(CollectionRepositoryContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 5;
        $entity->shouldReceive('morphRelationName')->andReturn('organizations');
        $entity->shouldReceive('canUserManageEntity')
            ->once()
            ->with(Mockery::any(), Role::MANAGER)
            ->andReturn(true);

        $user = Mockery::mock(UserFixture::class);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $request = $this->makeIndexRequest('Polis\\Http\\Core\\Requests\\Entity\\Collection\\IndexRequest');
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $repo->shouldReceive('findAll')
            ->once()
            ->with(
                Mockery::on(fn (array $filter) => ! in_array(['is_public', '=', '1'], $filter, true)),
                [], [], [], 10, [], 1,
            )
            ->andReturn($paginator);

        $this->assertSame($paginator, (new CollectionController($repo))->index($request, $entity));
    }

    public function test_store_stamps_owner_and_returns_201(): void
    {
        $repo = Mockery::mock(CollectionRepositoryContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 5;
        $entity->shouldReceive('morphRelationName')->andReturn('organizations');

        $payload = ['name' => 'New Collection'];
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Entity\\Collection\\StoreRequest', $payload);

        $created = Mockery::mock(BaseModelAbstract::class);
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'New Collection',
                'owner_id' => 5,
                'owner_type' => 'organizations',
            ])
            ->andReturn($created);

        $response = (new CollectionController($repo))->store($request, $entity);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }
}
