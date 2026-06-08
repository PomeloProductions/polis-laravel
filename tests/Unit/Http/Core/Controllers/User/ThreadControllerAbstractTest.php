<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\User;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Repositories\Messaging\ThreadRepositoryContract;
use Polis\Tests\Fixtures\Controllers\User\ThreadController;
use Polis\Tests\Fixtures\Models\User as UserFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for User\ThreadControllerAbstract.
 *
 * store() appends the bound user's id into the threads users[] list.
 */
final class ThreadControllerAbstractTest extends ControllerTestCase
{
    public function test_index_scopes_findAll_to_parent_user(): void
    {
        $repo = Mockery::mock(ThreadRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest('App\\Http\\Core\\Requests\\User\\Thread\\IndexRequest');
        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 10, [$user], 1)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new ThreadController($repo))->index($request, $user));
    }

    public function test_store_appends_bound_user_id_into_users_list(): void
    {
        $repo = Mockery::mock(ThreadRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $user->id = 17;

        $payload = ['users' => [99], 'subject' => 'Hi'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\Thread\\StoreRequest', $payload);

        $created = Mockery::mock();
        $created->shouldReceive('toJson')->andReturn('{}');
        $repo->shouldReceive('create')
            ->once()
            ->with(['users' => [99, 17], 'subject' => 'Hi'])
            ->andReturn($created);

        $response = (new ThreadController($repo))->store($request, $user);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }
}
