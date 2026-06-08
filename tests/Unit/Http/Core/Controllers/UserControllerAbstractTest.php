<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Contracts\Services\StripeCustomerServiceContract;
use Polis\Tests\Fixtures\Controllers\UserController;
use Polis\Tests\Fixtures\Models\User as UserFixture;

/**
 * Unit coverage for UserControllerAbstract.
 *
 * Standard CRUD + a me() route that pulls auth()->user() and reloads it
 * with the requested expand relations.
 */
final class UserControllerAbstractTest extends ControllerTestCase
{
    public function test_index_forwards_parsed_query_args(): void
    {
        $repo = Mockery::mock(UserRepositoryContract::class);
        $stripe = Mockery::mock(StripeCustomerServiceContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $request = $this->makeIndexRequest('App\\Http\\Core\\Requests\\User\\IndexRequest');

        $repo->shouldReceive('findAll')->once()->andReturn($paginator);

        $this->assertSame($paginator, (new UserController($repo, $stripe))->index($request));
    }

    public function test_store_creates_and_returns_201(): void
    {
        $repo = Mockery::mock(UserRepositoryContract::class);
        $stripe = Mockery::mock(StripeCustomerServiceContract::class);
        $payload = ['email' => 'new@example.test'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\StoreRequest', $payload);

        $created = Mockery::mock(UserFixture::class);
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')->once()->with($payload)->andReturn($created);

        $response = (new UserController($repo, $stripe))->store($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_show_loads_expand_on_bound_user(): void
    {
        $repo = Mockery::mock(UserRepositoryContract::class);
        $stripe = Mockery::mock(StripeCustomerServiceContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\ViewRequest', [
            'with' => ['contacts'],
        ]);

        $user = Mockery::mock(UserFixture::class);
        $loaded = Mockery::mock(UserFixture::class);
        $user->shouldReceive('load')->once()->with(['contacts'])->andReturn($loaded);

        $this->assertSame($loaded, (new UserController($repo, $stripe))->show($request, $user));
    }

    public function test_update_delegates_to_repository(): void
    {
        $repo = Mockery::mock(UserRepositoryContract::class);
        $stripe = Mockery::mock(StripeCustomerServiceContract::class);
        $payload = ['first_name' => 'Updated'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\UpdateRequest', $payload);

        $user = Mockery::mock(UserFixture::class);
        $updated = Mockery::mock(UserFixture::class);
        $repo->shouldReceive('update')->once()->with($user, $payload)->andReturn($updated);

        $this->assertSame($updated, (new UserController($repo, $stripe))->update($request, $user));
    }

    public function test_me_returns_logged_in_user_with_expand(): void
    {
        $repo = Mockery::mock(UserRepositoryContract::class);
        $stripe = Mockery::mock(StripeCustomerServiceContract::class);

        $user = Mockery::mock(UserFixture::class);
        $loaded = Mockery::mock(UserFixture::class);
        $loaded->shouldReceive('toJson')->andReturn('{"id":1}');
        $user->shouldReceive('load')->once()->with(['contacts'])->andReturn($loaded);

        // auth()->user() routes through the helper -> Auth\Factory ->
        // Guard. Bind a mock Auth\Factory at the container key so the
        // helper resolves through us.
        $guard = Mockery::mock(\Illuminate\Contracts\Auth\Guard::class);
        $guard->shouldReceive('user')->andReturn($user);
        $authFactory = Mockery::mock(\Illuminate\Contracts\Auth\Factory::class);
        $authFactory->shouldReceive('guard')->andReturn($guard);
        $authFactory->shouldReceive('user')->andReturn($user);
        app()->instance(\Illuminate\Contracts\Auth\Factory::class, $authFactory);

        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\MeRequest', [
            'with' => ['contacts'],
        ]);

        $response = (new UserController($repo, $stripe))->me($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function test_destroy_deletes_and_returns_204_json(): void
    {
        $repo = Mockery::mock(UserRepositoryContract::class);
        $stripe = Mockery::mock(StripeCustomerServiceContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\DeleteRequest');

        $user = Mockery::mock(UserFixture::class);
        $repo->shouldReceive('delete')->once()->with($user);

        $response = (new UserController($repo, $stripe))->destroy($request, $user);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }
}
