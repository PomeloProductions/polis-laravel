<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Wiki;

use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Repositories\Wiki\ArticleSummaryRepositoryContract;
use Polis\Tests\Fixtures\Controllers\Wiki\ArticleSummaryController;
use Polis\Tests\Fixtures\Models\Article as ArticleFixture;
use Polis\Tests\Fixtures\Models\ArticleSummary as ArticleSummaryFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for Wiki\ArticleSummaryControllerAbstract.
 *
 * Article-scoped CRUD where show/update/destroy each pivot on whether
 * the article currently has a summary (`$article->articleSummary`).
 */
final class ArticleSummaryControllerAbstractTest extends ControllerTestCase
{
    public function test_show_returns_404_when_article_has_no_summary(): void
    {
        $repo = Mockery::mock(ArticleSummaryRepositoryContract::class);
        $article = new ArticleFixture;
        // articleSummary attribute defaults to null on a parentless model.

        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Wiki\\ArticleSummary\\ViewRequest');

        $response = (new ArticleSummaryController($repo))->show($request, $article);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['message' => 'Article summary not found.'], json_decode($response->getContent(), true));
    }

    public function test_show_returns_summary_when_present(): void
    {
        $repo = Mockery::mock(ArticleSummaryRepositoryContract::class);

        $summary = Mockery::mock(ArticleSummaryFixture::class);
        $summary->shouldReceive('toJson')->andReturn('{"id":1}');
        $article = new ArticleFixture;
        $article->articleSummary = $summary;

        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Wiki\\ArticleSummary\\ViewRequest');
        $response = (new ArticleSummaryController($repo))->show($request, $article);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($summary, $response->getOriginalContent());
    }

    public function test_store_attaches_article_id_and_creates_201(): void
    {
        $repo = Mockery::mock(ArticleSummaryRepositoryContract::class);
        $article = new ArticleFixture;
        $article->id = 14;

        $payload = ['summary_text' => 'short summary'];
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Wiki\\ArticleSummary\\StoreRequest', $payload);

        $created = Mockery::mock(ArticleSummaryFixture::class);
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')
            ->once()
            ->with(['summary_text' => 'short summary', 'article_id' => 14])
            ->andReturn($created);

        $response = (new ArticleSummaryController($repo))->store($request, $article);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_update_returns_404_when_no_summary_to_update(): void
    {
        $repo = Mockery::mock(ArticleSummaryRepositoryContract::class);
        $article = new ArticleFixture;

        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Wiki\\ArticleSummary\\UpdateRequest');
        $response = (new ArticleSummaryController($repo))->update($request, $article);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_update_delegates_to_repository_when_summary_exists(): void
    {
        $repo = Mockery::mock(ArticleSummaryRepositoryContract::class);

        $summary = Mockery::mock(ArticleSummaryFixture::class);
        $article = new ArticleFixture;
        $article->articleSummary = $summary;

        $updated = Mockery::mock(ArticleSummaryFixture::class);
        $updated->shouldReceive('toJson')->andReturn('{"id":1}');
        $payload = ['summary_text' => 'updated body'];

        $repo->shouldReceive('update')->once()->with($summary, $payload)->andReturn($updated);

        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Wiki\\ArticleSummary\\UpdateRequest', $payload);
        $response = (new ArticleSummaryController($repo))->update($request, $article);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($updated, $response->getOriginalContent());
    }

    public function test_destroy_returns_404_when_no_summary_to_destroy(): void
    {
        $repo = Mockery::mock(ArticleSummaryRepositoryContract::class);
        $article = new ArticleFixture;

        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Wiki\\ArticleSummary\\DeleteRequest');
        $response = (new ArticleSummaryController($repo))->destroy($request, $article);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_destroy_deletes_and_returns_204_when_summary_exists(): void
    {
        $repo = Mockery::mock(ArticleSummaryRepositoryContract::class);

        $summary = Mockery::mock(ArticleSummaryFixture::class);
        $article = new ArticleFixture;
        $article->articleSummary = $summary;

        $repo->shouldReceive('delete')->once()->with($summary);

        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Wiki\\ArticleSummary\\DeleteRequest');
        $response = (new ArticleSummaryController($repo))->destroy($request, $article);

        $this->assertSame(204, $response->getStatusCode());
    }
}
