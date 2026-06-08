<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Organization;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Polis\Contracts\Repositories\Organization\OrganizationManagerRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Events\Organization\OrganizationManagerCreatedEvent;
use Polis\Tests\Fixtures\Controllers\Organization\OrganizationManagerController;
use Polis\Tests\Fixtures\Models\Organization as OrganizationFixture;
use Polis\Tests\Fixtures\Models\OrganizationManager as OrganizationManagerFixture;
use Polis\Tests\Fixtures\Models\User as UserFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for Organization\OrganizationManagerControllerAbstract.
 *
 * The store() branch has two sub-paths: (a) the email belongs to an
 * existing user, and (b) the email is new so a temp-password user is
 * created. Both then dispatch an OrganizationManagerCreatedEvent.
 */
final class OrganizationManagerControllerAbstractTest extends ControllerTestCase
{
    public function test_index_scopes_findAll_to_parent_organization(): void
    {
        $repo = Mockery::mock(OrganizationManagerRepositoryContract::class);
        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $request = $this->makeIndexRequest(
            'App\\Http\\Core\\Requests\\Organization\\OrganizationManager\\IndexRequest',
        );
        $org = Mockery::mock(OrganizationFixture::class);

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 10, [$org], 1)
            ->andReturn($paginator);

        $this->assertSame(
            $paginator,
            (new OrganizationManagerController($repo, $userRepo, $dispatcher))->index($request, $org),
        );
    }

    public function test_store_links_existing_user_when_email_matches(): void
    {
        $repo = Mockery::mock(OrganizationManagerRepositoryContract::class);
        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $org = Mockery::mock(OrganizationFixture::class);

        $payload = ['email' => 'exists@example.test', 'role_id' => 11];

        $existingUser = Mockery::mock(UserFixture::class);
        $existingUser->id = 88;
        $userRepo->shouldReceive('findByEmail')
            ->once()
            ->with('exists@example.test')
            ->andReturn($existingUser);
        // No user create call when the email is already known
        $userRepo->shouldNotReceive('create');

        $created = Mockery::mock(OrganizationManagerFixture::class);
        $created->shouldReceive('toJson')->andReturn('{}');
        $repo->shouldReceive('create')
            ->once()
            ->with(['role_id' => 11, 'user_id' => 88], $org)
            ->andReturn($created);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(OrganizationManagerCreatedEvent::class));

        $request = $this->makeRequest(
            'App\\Http\\Core\\Requests\\Organization\\OrganizationManager\\StoreRequest',
            $payload,
        );

        $response = (new OrganizationManagerController($repo, $userRepo, $dispatcher))
            ->store($request, $org);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_store_creates_user_with_temp_password_when_email_is_new(): void
    {
        $repo = Mockery::mock(OrganizationManagerRepositoryContract::class);
        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $org = Mockery::mock(OrganizationFixture::class);

        $payload = ['email' => 'newhire@example.test', 'role_id' => 11];

        $userRepo->shouldReceive('findByEmail')
            ->once()
            ->with('newhire@example.test')
            ->andReturn(null);

        $newUser = Mockery::mock(UserFixture::class);
        $newUser->id = 555;
        $userRepo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $args) => $args['email'] === 'newhire@example.test'
                && is_string($args['password'])))
            ->andReturn($newUser);

        $created = Mockery::mock(OrganizationManagerFixture::class);
        $created->shouldReceive('toJson')->andReturn('{}');
        $repo->shouldReceive('create')
            ->once()
            ->with(['role_id' => 11, 'user_id' => 555], $org)
            ->andReturn($created);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(OrganizationManagerCreatedEvent::class));

        $request = $this->makeRequest(
            'App\\Http\\Core\\Requests\\Organization\\OrganizationManager\\StoreRequest',
            $payload,
        );

        $response = (new OrganizationManagerController($repo, $userRepo, $dispatcher))
            ->store($request, $org);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_update_delegates_to_repository(): void
    {
        $repo = Mockery::mock(OrganizationManagerRepositoryContract::class);
        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $dispatcher = Mockery::mock(Dispatcher::class);

        $org = Mockery::mock(OrganizationFixture::class);
        $manager = Mockery::mock(OrganizationManagerFixture::class);
        $updated = Mockery::mock(OrganizationManagerFixture::class);
        $payload = ['role_id' => 10];

        $repo->shouldReceive('update')->once()->with($manager, $payload)->andReturn($updated);

        $request = $this->makeRequest(
            'App\\Http\\Core\\Requests\\Organization\\OrganizationManager\\UpdateRequest',
            $payload,
        );

        $this->assertSame(
            $updated,
            (new OrganizationManagerController($repo, $userRepo, $dispatcher))->update($request, $org, $manager),
        );
    }

    public function test_destroy_deletes_and_returns_204(): void
    {
        $repo = Mockery::mock(OrganizationManagerRepositoryContract::class);
        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $dispatcher = Mockery::mock(Dispatcher::class);

        $org = Mockery::mock(OrganizationFixture::class);
        $manager = Mockery::mock(OrganizationManagerFixture::class);
        $repo->shouldReceive('delete')->once()->with($manager);

        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Organization\\OrganizationManager\\DeleteRequest');
        $response = (new OrganizationManagerController($repo, $userRepo, $dispatcher))
            ->destroy($request, $org, $manager);

        $this->assertSame(204, $response->getStatusCode());
    }
}
