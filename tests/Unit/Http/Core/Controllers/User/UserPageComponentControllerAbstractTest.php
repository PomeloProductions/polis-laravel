<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\User;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract;
use Polis\Tests\Fixtures\Controllers\User\UserPageComponentController;
use Polis\Tests\Fixtures\Models\User as UserFixture;
use Polis\Tests\Fixtures\Models\UserPage as UserPageFixture;
use Polis\Tests\Fixtures\Models\UserPageComponent as UserPageComponentFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for User\UserPageComponentControllerAbstract.
 *
 * UserPage-scoped CRUD with auto-display-order calculation in store().
 */
final class UserPageComponentControllerAbstractTest extends ControllerTestCase
{
    public function test_index_scopes_find_all_to_parent_page(): void
    {
        $repo = Mockery::mock(UserPageComponentRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $page = Mockery::mock(UserPageFixture::class);
        $page->id = 33;
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest('Polis\\Http\\Core\\Requests\\User\\UserPageComponent\\IndexRequest');

        $repo->shouldReceive('findAll')
            ->once()
            ->with(
                Mockery::on(fn (array $filter) => in_array(['user_page_id', '=', 33], $filter, true)),
                [],
                ['display_order' => 'asc'],
                [], 10, [], 1,
            )
            ->andReturn($paginator);

        $this->assertSame(
            $paginator,
            (new UserPageComponentController($repo))->index($request, $user, $page),
        );
    }

    public function test_store_calculates_next_display_order_when_unset(): void
    {
        $repo = Mockery::mock(UserPageComponentRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $page = Mockery::mock(UserPageFixture::class);
        $page->id = 42;

        $payload = ['component_type' => 'TextBlock', 'props' => ['text' => 'Hi']];
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\User\\UserPageComponent\\StoreRequest', $payload);

        $existing = new Collection;
        $existing->push((object) ['display_order' => 7]);
        $repo->shouldReceive('findAll')
            ->once()
            ->with([['user_page_id', '=', 42]])
            ->andReturn($existing);

        $created = Mockery::mock(UserPageComponentFixture::class);
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $data) => $data['user_page_id'] === 42
                && $data['display_order'] === 8))
            ->andReturn($created);

        $response = (new UserPageComponentController($repo))->store($request, $user, $page);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_update_delegates_to_repository(): void
    {
        $repo = Mockery::mock(UserPageComponentRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $page = Mockery::mock(UserPageFixture::class);
        $component = Mockery::mock(UserPageComponentFixture::class);
        $updated = Mockery::mock(UserPageComponentFixture::class);

        $payload = ['props' => ['updated' => true]];
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\User\\UserPageComponent\\UpdateRequest', $payload);

        $repo->shouldReceive('update')->once()->with($component, $payload)->andReturn($updated);

        $this->assertSame(
            $updated,
            (new UserPageComponentController($repo))->update($request, $user, $page, $component),
        );
    }

    public function test_destroy_deletes_and_returns_204(): void
    {
        $repo = Mockery::mock(UserPageComponentRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $page = Mockery::mock(UserPageFixture::class);
        $component = Mockery::mock(UserPageComponentFixture::class);

        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\User\\UserPageComponent\\DeleteRequest');
        $repo->shouldReceive('delete')->once()->with($component);

        $response = (new UserPageComponentController($repo))->destroy($request, $user, $page, $component);
        $this->assertSame(204, $response->getStatusCode());
    }
}
