<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Article;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Repositories\Wiki\ArticleVersionRepositoryContract;
use Polis\Tests\Fixtures\Controllers\Article\ArticleVersionController;
use Polis\Tests\Fixtures\Models\Article as ArticleFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for ArticleVersionControllerAbstract.
 *
 * Article-scoped: both index() and store() pass the parent Article into
 * the repository's belongsTo array / create-related-model argument.
 */
final class ArticleVersionControllerAbstractTest extends ControllerTestCase
{
    public function test_index_passes_article_into_belongs_to_array(): void
    {
        $repo = Mockery::mock(ArticleVersionRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $request = $this->makeIndexRequest(
            'App\\Http\\Core\\Requests\\Article\\ArticleVersion\\IndexRequest',
        );
        $article = Mockery::mock(ArticleFixture::class);

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 10, [$article], 1)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new ArticleVersionController($repo))->index($request, $article));
    }

    public function test_store_creates_under_article_and_returns_201(): void
    {
        $repo = Mockery::mock(ArticleVersionRepositoryContract::class);
        $payload = ['content' => 'v2 body'];
        $request = $this->makeRequest(
            'App\\Http\\Core\\Requests\\Article\\ArticleVersion\\StoreRequest',
            $payload,
        );
        $article = Mockery::mock(ArticleFixture::class);

        $created = Mockery::mock();
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')
            ->once()
            ->with($payload, $article)
            ->andReturn($created);

        $response = (new ArticleVersionController($repo))->store($request, $article);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }
}
