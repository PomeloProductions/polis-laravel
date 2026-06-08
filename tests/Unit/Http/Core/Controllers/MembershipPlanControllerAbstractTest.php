<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Routing\UrlGenerator;
use Mockery;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRepositoryContract;
use Polis\Tests\Fixtures\Controllers\MembershipPlanController;
use Polis\Tests\Fixtures\Models\MembershipPlan as MembershipPlanFixture;

/**
 * Unit coverage for MembershipPlanControllerAbstract.
 *
 * The non-vanilla wrinkle is store() — after creating the model the
 * controller attaches a Location header pointing at the show route via
 * Laravel's route() helper. Tests stub the URL generator so the helper
 * resolves without a real Router.
 */
final class MembershipPlanControllerAbstractTest extends ControllerTestCase
{
    public function test_index_forwards_parsed_query_args(): void
    {
        $repo = Mockery::mock(MembershipPlanRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $request = $this->makeIndexRequest('App\\Http\\Core\\Requests\\MembershipPlan\\IndexRequest');

        $repo->shouldReceive('findAll')->once()->with([], [], [], [], 10, [], 1)->andReturn($paginator);

        $this->assertSame($paginator, (new MembershipPlanController($repo))->index($request));
    }

    public function test_show_loads_expand(): void
    {
        $repo = Mockery::mock(MembershipPlanRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\MembershipPlan\\ViewRequest', [
            'with' => ['rates'],
        ]);

        $plan = Mockery::mock(MembershipPlanFixture::class);
        $loaded = Mockery::mock(MembershipPlanFixture::class);
        $plan->shouldReceive('load')->once()->with(['rates'])->andReturn($loaded);

        $this->assertSame($loaded, (new MembershipPlanController($repo))->show($request, $plan));
    }

    public function test_store_creates_and_attaches_location_header(): void
    {
        $repo = Mockery::mock(MembershipPlanRepositoryContract::class);
        $payload = ['name' => 'Pro'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\MembershipPlan\\StoreRequest', $payload);

        $created = Mockery::mock(MembershipPlanFixture::class);
        $created->shouldReceive('toJson')->andReturn('{"id":7}');
        $repo->shouldReceive('create')->once()->with($payload)->andReturn($created);

        // route() helper resolves through the UrlGenerator; stub a return
        // value so the controller can attach the Location header.
        $url = Mockery::mock(UrlGenerator::class);
        $url->shouldReceive('route')
            ->once()
            ->with('v1.membership-plans.show', Mockery::any(), true)
            ->andReturn('http://localhost/v1/membership-plans/7');
        app()->instance('url', $url);

        $response = (new MembershipPlanController($repo))->store($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('http://localhost/v1/membership-plans/7', $response->headers->get('Location'));
    }

    public function test_update_delegates_to_repository(): void
    {
        $repo = Mockery::mock(MembershipPlanRepositoryContract::class);
        $payload = ['name' => 'Pro+'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\MembershipPlan\\UpdateRequest', $payload);

        $plan = Mockery::mock(MembershipPlanFixture::class);
        $updated = Mockery::mock(MembershipPlanFixture::class);
        $repo->shouldReceive('update')->once()->with($plan, $payload)->andReturn($updated);

        $this->assertSame($updated, (new MembershipPlanController($repo))->update($request, $plan));
    }

    public function test_destroy_deletes_and_returns_204(): void
    {
        $repo = Mockery::mock(MembershipPlanRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\MembershipPlan\\DeleteRequest');

        $plan = Mockery::mock(MembershipPlanFixture::class);
        $repo->shouldReceive('delete')->once()->with($plan);

        $this->assertSame(204, (new MembershipPlanController($repo))->destroy($request, $plan)->getStatusCode());
    }
}
