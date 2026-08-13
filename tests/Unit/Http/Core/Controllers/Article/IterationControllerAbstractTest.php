<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Article;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Polis\Contracts\Repositories\Wiki\ArticleIterationRepositoryContract;
use Polis\Tests\Fixtures\Controllers\Article\IterationController;
use Polis\Tests\Fixtures\Models\Article as ArticleFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for IterationControllerAbstract.
 *
 * Article-scoped read-only listing.
 */
final class IterationControllerAbstractTest extends ControllerTestCase
{
    public function test_index_scopes_find_all_to_parent_article(): void
    {
        $repo = Mockery::mock(ArticleIterationRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $request = $this->makeIndexRequest(
            'Polis\\Http\\Core\\Requests\\Article\\Iteration\\IndexRequest',
            ['limit' => 50],
        );
        $article = Mockery::mock(ArticleFixture::class);

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 50, [$article], 1)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new IterationController($repo))->index($request, $article));
    }
}
