<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Polis\Contracts\Repositories\Wiki\ArticleRepositoryContract;
use Polis\Tests\Fixtures\Controllers\ArticleController;
use Polis\Tests\Fixtures\Models\Article as ArticleFixture;

/**
 * Unit coverage for ArticleControllerAbstract.
 *
 * Mostly a vanilla CRUD wrapper around ArticleRepositoryContract; the
 * extra wrinkle is store() injects the logged-in user as created_by_id,
 * so this test pins the Auth::user() integration and the resulting
 * repository payload shape.
 */
final class ArticleControllerAbstractTest extends ControllerTestCase
{
    public function test_index_forwards_parsed_query_args_to_repository_find_all(): void
    {
        $repo = Mockery::mock(ArticleRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest(
            'Polis\\Http\\Core\\Requests\\Article\\IndexRequest',
            ['limit' => 5, 'page' => 2],
        );

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 5, [], 2)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new ArticleController($repo))->index($request));
    }

    public function test_show_loads_specified_relations_on_the_bound_article(): void
    {
        $repo = Mockery::mock(ArticleRepositoryContract::class);
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Article\\ViewRequest', [
            'with' => ['versions'],
        ]);

        $article = Mockery::mock(ArticleFixture::class);
        $loaded = Mockery::mock(ArticleFixture::class);
        $article->shouldReceive('load')->once()->with(['versions'])->andReturn($loaded);

        $this->assertSame($loaded, (new ArticleController($repo))->show($request, $article));
    }

    public function test_store_attaches_logged_in_user_id_as_created_by_and_returns_201(): void
    {
        $repo = Mockery::mock(ArticleRepositoryContract::class);

        $user = Mockery::mock(Authenticatable::class);
        $user->id = 99;
        Auth::shouldReceive('user')->once()->andReturn($user);

        $payload = ['title' => 'New article'];
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Article\\StoreRequest', $payload);

        $created = Mockery::mock(ArticleFixture::class);
        // JsonResponse calls toJson() on the model when serializing.
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')
            ->once()
            ->with(['title' => 'New article', 'created_by_id' => 99])
            ->andReturn($created);

        $response = (new ArticleController($repo))->store($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($created, $response->getOriginalContent());
    }

    public function test_update_delegates_to_repository_with_request_body(): void
    {
        $repo = Mockery::mock(ArticleRepositoryContract::class);
        $payload = ['title' => 'Renamed'];

        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Article\\UpdateRequest', $payload);
        $model = Mockery::mock(ArticleFixture::class);
        $updated = Mockery::mock(ArticleFixture::class);

        $repo->shouldReceive('update')->once()->with($model, $payload)->andReturn($updated);

        $this->assertSame($updated, (new ArticleController($repo))->update($request, $model));
    }
}
