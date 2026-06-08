<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\User;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Repositories\User\ArticleNoteRepositoryContract;
use Polis\Contracts\Repositories\Wiki\ArticleRepositoryContract;
use Polis\Tests\Fixtures\Controllers\User\ArticleNoteController;
use Polis\Tests\Fixtures\Models\ArticleNote as ArticleNoteFixture;
use Polis\Tests\Fixtures\Models\User as UserFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for User\ArticleNoteControllerAbstract.
 *
 * Most actions are vanilla User-scoped CRUD. randomArticle() has one
 * branch we can exercise here (404 when the article-repo's selector
 * returns null); the happy path requires mocking the static
 * ArticleNote::where(...)->first() chain which we leave uncovered (see
 * the fixture's docblock for the rationale).
 */
final class ArticleNoteControllerAbstractTest extends ControllerTestCase
{
    public function test_index_scopes_findAll_to_parent_user(): void
    {
        $repo = Mockery::mock(ArticleNoteRepositoryContract::class);
        $articleRepo = Mockery::mock(ArticleRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);

        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $request = $this->makeIndexRequest('App\\Http\\Core\\Requests\\User\\ArticleNote\\IndexRequest');

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 10, [$user], 1)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new ArticleNoteController($repo, $articleRepo))->index($request, $user));
    }

    public function test_store_attaches_user_id_and_creates(): void
    {
        $repo = Mockery::mock(ArticleNoteRepositoryContract::class);
        $articleRepo = Mockery::mock(ArticleRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $user->id = 21;

        $payload = ['article_id' => 9, 'note' => 'A note'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\ArticleNote\\StoreRequest', $payload);

        $created = Mockery::mock(ArticleNoteFixture::class);
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')
            ->once()
            ->with(['article_id' => 9, 'note' => 'A note', 'user_id' => 21])
            ->andReturn($created);

        $response = (new ArticleNoteController($repo, $articleRepo))->store($request, $user);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_show_returns_bound_article_note(): void
    {
        $repo = Mockery::mock(ArticleNoteRepositoryContract::class);
        $articleRepo = Mockery::mock(ArticleRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $note = Mockery::mock(ArticleNoteFixture::class);

        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\ArticleNote\\ViewRequest');

        $this->assertSame(
            $note,
            (new ArticleNoteController($repo, $articleRepo))->show($request, $user, $note),
        );
    }

    public function test_update_delegates_to_repository(): void
    {
        $repo = Mockery::mock(ArticleNoteRepositoryContract::class);
        $articleRepo = Mockery::mock(ArticleRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);

        $payload = ['note' => 'Edited'];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\ArticleNote\\UpdateRequest', $payload);

        $note = Mockery::mock(ArticleNoteFixture::class);
        $updated = Mockery::mock(ArticleNoteFixture::class);
        $repo->shouldReceive('update')->once()->with($note, $payload)->andReturn($updated);

        $this->assertSame(
            $updated,
            (new ArticleNoteController($repo, $articleRepo))->update($request, $user, $note),
        );
    }

    public function test_destroy_deletes_and_returns_204(): void
    {
        $repo = Mockery::mock(ArticleNoteRepositoryContract::class);
        $articleRepo = Mockery::mock(ArticleRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $note = Mockery::mock(ArticleNoteFixture::class);

        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\ArticleNote\\DeleteRequest');
        $repo->shouldReceive('delete')->once()->with($note);

        $response = (new ArticleNoteController($repo, $articleRepo))->destroy($request, $user, $note);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_random_article_returns_404_when_no_article_available(): void
    {
        $repo = Mockery::mock(ArticleNoteRepositoryContract::class);
        $articleRepo = Mockery::mock(ArticleRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);

        $articleRepo->shouldReceive('selectArticleForUser')->once()->with($user)->andReturn(null);

        $request = $this->makeRequest('App\\Http\\Core\\Requests\\User\\ArticleNote\\RandomArticleRequest');
        $response = (new ArticleNoteController($repo, $articleRepo))->randomArticle($request, $user);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(
            ['message' => 'No available articles found.'],
            json_decode($response->getContent(), true),
        );
    }
}
