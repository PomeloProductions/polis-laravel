<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Wiki;

use App\Models\Wiki\Article;
use Mockery;
use Polis\Contracts\Repositories\Statistic\StatisticRepositoryContract;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\Wiki\ArticleRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for ArticleRepository — the create/update overrides that sync
 * categories via the pivot, the NotImplemented delete trait, and the
 * constructor signature.
 *
 * The selectArticleForUser query is exercised separately in the
 * integration suite (its SQL uses JSON_EXTRACT which is sqlite-friendly
 * but requires a full statistic graph that we don't bring up in standalone
 * tests).
 */
final class ArticleRepositoryTest extends TestCase
{
    public function test_delete_throws_not_implemented(): void
    {
        $modelMock = Mockery::mock(Article::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);
        $statsRepo = Mockery::mock(StatisticRepositoryContract::class);

        $repo = new ArticleRepository($modelMock, $this->getGenericLogMock(), $statsRepo);

        $this->expectException(NotImplementedException::class);
        $repo->delete(Mockery::mock(\Polis\Models\BaseModelAbstract::class));
    }

    public function test_create_with_categories_syncs_pivot(): void
    {
        $modelMock = Mockery::mock(Article::class);
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;
        // The sync receives keyed-by-category-id array with relevance pivot
        $modelMock->shouldReceive('categories->sync')
            ->once()
            ->withArgs(function ($arg) {
                $this->assertArrayHasKey(7, $arg);
                $this->assertSame(['relevance' => 0.9], $arg[7]);

                return true;
            });

        $statsRepo = Mockery::mock(StatisticRepositoryContract::class);

        $repo = new ArticleRepository($modelMock, $this->getGenericLogMock(), $statsRepo);
        $repo->create([
            'title' => 'X',
            'categories' => [
                ['category_id' => 7, 'relevance' => 0.9],
            ],
        ]);
    }

    public function test_create_without_categories_does_not_sync(): void
    {
        $modelMock = Mockery::mock(Article::class);
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;
        $modelMock->shouldNotReceive('categories->sync');

        $statsRepo = Mockery::mock(StatisticRepositoryContract::class);

        $repo = new ArticleRepository($modelMock, $this->getGenericLogMock(), $statsRepo);
        $repo->create(['title' => 'X']);
    }

    public function test_create_with_categories_filters_null_relevance_out(): void
    {
        $modelMock = Mockery::mock(Article::class);
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;
        $modelMock->shouldReceive('categories->sync')
            ->once()
            ->withArgs(function ($arg) {
                // relevance was null -> array_filter strips it
                $this->assertArrayHasKey(3, $arg);
                $this->assertSame([], $arg[3]);

                return true;
            });

        $statsRepo = Mockery::mock(StatisticRepositoryContract::class);

        $repo = new ArticleRepository($modelMock, $this->getGenericLogMock(), $statsRepo);
        $repo->create(['categories' => [['category_id' => 3]]]);
    }

    public function test_update_with_categories_syncs_pivot(): void
    {
        $modelMock = Mockery::mock(Article::class);
        $modelMock->shouldReceive('update')->once()->andReturn(true);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->shouldReceive('categories->sync')->once();

        $statsRepo = Mockery::mock(StatisticRepositoryContract::class);

        $repo = new ArticleRepository($modelMock, $this->getGenericLogMock(), $statsRepo);
        $repo->update($modelMock, [
            'title' => 'changed',
            'categories' => [['category_id' => 9, 'relevance' => 0.5]],
        ]);
    }

    public function test_update_without_categories_does_not_sync(): void
    {
        $modelMock = Mockery::mock(Article::class);
        $modelMock->shouldReceive('update')->once()->andReturn(true);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->shouldNotReceive('categories->sync');

        $statsRepo = Mockery::mock(StatisticRepositoryContract::class);

        $repo = new ArticleRepository($modelMock, $this->getGenericLogMock(), $statsRepo);
        $repo->update($modelMock, ['title' => 'changed']);
    }
}
