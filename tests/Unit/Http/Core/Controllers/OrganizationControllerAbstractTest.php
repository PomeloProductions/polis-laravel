<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Polis\Contracts\Repositories\Organization\OrganizationManagerRepositoryContract;
use Polis\Contracts\Repositories\Organization\OrganizationRepositoryContract;
use Polis\Tests\Fixtures\Controllers\OrganizationController;
use Polis\Tests\Fixtures\Models\Organization as OrganizationFixture;
use Polis\Tests\Fixtures\Models\Role;

/**
 * Unit coverage for OrganizationControllerAbstract.
 *
 * store() does two things on top of the canonical pattern:
 *   1. Creates an OrganizationManager record linking the creator user
 *      to the new org as Role::ADMINISTRATOR.
 *   2. Attaches a Location header pointing at the show route.
 */
final class OrganizationControllerAbstractTest extends ControllerTestCase
{
    public function test_index_forwards_parsed_query_args(): void
    {
        $repo = Mockery::mock(OrganizationRepositoryContract::class);
        $managerRepo = Mockery::mock(OrganizationManagerRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $request = $this->makeIndexRequest('App\\Http\\Core\\Requests\\Organization\\IndexRequest');

        $repo->shouldReceive('findAll')->once()->andReturn($paginator);

        $this->assertSame($paginator, (new OrganizationController($repo, $managerRepo))->index($request));
    }

    public function test_show_loads_expand(): void
    {
        $repo = Mockery::mock(OrganizationRepositoryContract::class);
        $managerRepo = Mockery::mock(OrganizationManagerRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Organization\\ViewRequest', [
            'with' => ['managers'],
        ]);

        $org = Mockery::mock(OrganizationFixture::class);
        $loaded = Mockery::mock(OrganizationFixture::class);
        $org->shouldReceive('load')->once()->with(['managers'])->andReturn($loaded);

        $this->assertSame($loaded, (new OrganizationController($repo, $managerRepo))->show($request, $org));
    }

    public function test_store_creates_org_and_administrator_record_and_attaches_location(): void
    {
        $repo = Mockery::mock(OrganizationRepositoryContract::class);
        $managerRepo = Mockery::mock(OrganizationManagerRepositoryContract::class);

        $user = Mockery::mock(Authenticatable::class);
        $user->id = 9;
        Auth::shouldReceive('user')->once()->andReturn($user);

        $payload = ['name' => 'Acme'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Organization\\StoreRequest', $payload);

        $created = Mockery::mock(OrganizationFixture::class);
        $created->id = 7;
        $created->shouldReceive('toJson')->andReturn('{"id":7,"name":"Acme"}');

        $repo->shouldReceive('create')->once()->with($payload)->andReturn($created);
        $managerRepo->shouldReceive('create')
            ->once()
            ->with([
                'organization_id' => 7,
                'role_id' => Role::ADMINISTRATOR,
                'user_id' => 9,
            ])
            ->andReturn(Mockery::mock(\Polis\Models\BaseModelAbstract::class));

        $url = Mockery::mock(UrlGenerator::class);
        $url->shouldReceive('route')
            ->once()
            ->with('v1.organizations.show', Mockery::any(), true)
            ->andReturn('http://localhost/v1/organizations/7');
        app()->instance('url', $url);

        $response = (new OrganizationController($repo, $managerRepo))->store($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('http://localhost/v1/organizations/7', $response->headers->get('Location'));
    }

    public function test_update_delegates_to_repository(): void
    {
        $repo = Mockery::mock(OrganizationRepositoryContract::class);
        $managerRepo = Mockery::mock(OrganizationManagerRepositoryContract::class);
        $payload = ['name' => 'Renamed'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Organization\\UpdateRequest', $payload);

        $org = Mockery::mock(OrganizationFixture::class);
        $updated = Mockery::mock(OrganizationFixture::class);
        $repo->shouldReceive('update')->once()->with($org, $payload)->andReturn($updated);

        $this->assertSame($updated, (new OrganizationController($repo, $managerRepo))->update($request, $org));
    }

    public function test_destroy_deletes_and_returns_204(): void
    {
        $repo = Mockery::mock(OrganizationRepositoryContract::class);
        $managerRepo = Mockery::mock(OrganizationManagerRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Organization\\DeleteRequest');

        $org = Mockery::mock(OrganizationFixture::class);
        $repo->shouldReceive('delete')->once()->with($org);

        $this->assertSame(204, (new OrganizationController($repo, $managerRepo))->destroy($request, $org)->getStatusCode());
    }
}
