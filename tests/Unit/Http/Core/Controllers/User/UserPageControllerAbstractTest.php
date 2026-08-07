<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\User;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Repositories\User\UserPageRepositoryContract;
use Polis\Tests\Fixtures\Controllers\User\UserPageController;
use Polis\Tests\Fixtures\Models\User as UserFixture;
use Polis\Tests\Fixtures\Models\UserPage as UserPageFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for User\UserPageControllerAbstract.
 *
 * Adds a slug-generator branch (when one isn't supplied) and a
 * display-order calculator (next-after-max-existing). Also strips
 * slug/route_path/page_type/is_required fields from updates when the
 * existing page is marked is_required=true.
 */
final class UserPageControllerAbstractTest extends ControllerTestCase
{
    public function test_index_scopes_find_all_to_parent_user_with_display_order(): void
    {
        $repo = Mockery::mock(UserPageRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $user->id = 5;
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest('App\\Http\\Core\\Requests\\User\\UserPage\\IndexRequest');

        $repo->shouldReceive('findAll')
            ->once()
            ->with(
                Mockery::on(fn (array $filter) => in_array(['user_id', '=', 5], $filter, true)),
                [],
                ['display_order' => 'asc'],
                [], 10, [], 1,
            )
            ->andReturn($paginator);

        $this->assertSame($paginator, (new UserPageController($repo))->index($request, $user));
    }

    public function test_store_auto_generates_slug_and_appends_to_display_order_max_when_omitted(): void
    {
        $repo = Mockery::mock(UserPageRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $user->id = 7;
        $user->shouldReceive('morphRelationName')->andReturn('user');

        $payload = ['name' => 'My New Page'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\UserPage\\StoreRequest', $payload);

        // Slug-uniqueness loop: first lookup returns empty Collection so the
        // base slug 'my-new-page' is kept.
        $emptyCollection = new Collection;
        $repo->shouldReceive('findAll')
            ->once()
            ->with([
                ['user_id', '=', 7],
                ['slug', '=', 'my-new-page'],
            ])
            ->andReturn($emptyCollection);
        // display_order auto-calculation:
        $existing = new Collection;
        $existing->push((object) ['display_order' => 4]);
        $repo->shouldReceive('findAll')
            ->once()
            ->with([['user_id', '=', 7]])
            ->andReturn($existing);

        $created = Mockery::mock(UserPageFixture::class);
        $created->shouldReceive('load')->once()->with('components')->andReturnSelf();
        $created->shouldReceive('toJson')->andReturn('{"id":1}');

        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $data) => $data['user_id'] === 7
                && $data['owner_id'] === 7
                && $data['owner_type'] === 'user'
                && $data['slug'] === 'my-new-page'
                && $data['display_order'] === 5
                && $data['is_required'] === false
                && $data['is_visible'] === true
                && $data['icon'] === 'IconList'))
            ->andReturn($created);

        $response = (new UserPageController($repo))->store($request, $user);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_update_on_required_page_strips_locked_fields(): void
    {
        $repo = Mockery::mock(UserPageRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);

        $page = new UserPageFixture;
        $page->is_required = true;

        $payload = ['name' => 'Renamed', 'slug' => 'should-be-ignored', 'route_path' => '/x', 'page_type' => 'custom'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\UserPage\\UpdateRequest', $payload);

        $updated = Mockery::mock(UserPageFixture::class);
        $repo->shouldReceive('update')
            ->once()
            ->with($page, Mockery::on(fn (array $data) => $data === ['name' => 'Renamed']))
            ->andReturn($updated);

        $this->assertSame($updated, (new UserPageController($repo))->update($request, $user, $page));
    }

    public function test_update_on_non_required_page_passes_all_fields(): void
    {
        $repo = Mockery::mock(UserPageRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);

        $page = new UserPageFixture;
        $page->is_required = false;

        $payload = ['name' => 'Renamed', 'slug' => 'kept'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\UserPage\\UpdateRequest', $payload);

        $updated = Mockery::mock(UserPageFixture::class);
        $repo->shouldReceive('update')->once()->with($page, $payload)->andReturn($updated);

        $this->assertSame($updated, (new UserPageController($repo))->update($request, $user, $page));
    }

    public function test_store_appends_counter_when_slug_collides(): void
    {
        $repo = Mockery::mock(UserPageRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $user->id = 7;
        $user->shouldReceive('morphRelationName')->andReturn('user');

        // Name "1 Hot Page" starts with a digit; the slug helper prefixes
        // 'page-' in that branch.
        $payload = ['name' => '1 Hot Page'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\UserPage\\StoreRequest', $payload);

        $nonEmpty = new Collection;
        $nonEmpty->push((object) ['slug' => 'page-1-hot-page']);
        $empty = new Collection;

        $repo->shouldReceive('findAll')
            ->once()
            ->with([['user_id', '=', 7], ['slug', '=', 'page-1-hot-page']])
            ->andReturn($nonEmpty);
        $repo->shouldReceive('findAll')
            ->once()
            ->with([['user_id', '=', 7], ['slug', '=', 'page-1-hot-page-1']])
            ->andReturn($empty);

        // display_order lookup
        $repo->shouldReceive('findAll')
            ->once()
            ->with([['user_id', '=', 7]])
            ->andReturn(new Collection);

        $created = Mockery::mock(UserPageFixture::class);
        $created->shouldReceive('load')->once()->with('components')->andReturnSelf();
        $created->shouldReceive('toJson')->andReturn('{"id":1}');

        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $data) => $data['slug'] === 'page-1-hot-page-1'))
            ->andReturn($created);

        $response = (new UserPageController($repo))->store($request, $user);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_destroy_deletes_and_returns_204(): void
    {
        $repo = Mockery::mock(UserPageRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $page = Mockery::mock(UserPageFixture::class);

        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\UserPage\\DeleteRequest');
        $repo->shouldReceive('delete')->once()->with($page);

        $response = (new UserPageController($repo))->destroy($request, $user, $page);
        $this->assertSame(204, $response->getStatusCode());
    }
}
