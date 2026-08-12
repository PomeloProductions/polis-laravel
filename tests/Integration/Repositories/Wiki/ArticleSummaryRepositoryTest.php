<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleSummary;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Wiki\ArticleSummaryRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ArticleSummaryRepositoryTest
 */
final class ArticleSummaryRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var ArticleSummaryRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new ArticleSummaryRepository(
            new ArticleSummary,
            $this->getGenericLogMock()
        );
    }

    public function test_find_all_success(): void
    {
        ArticleSummary::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_find_or_fail_success(): void
    {
        $model = ArticleSummary::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        ArticleSummary::factory()->create(['id' => 2]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(1);
    }

    public function test_create_success(): void
    {
        $article = Article::factory()->create();

        /** @var ArticleSummary $summary */
        $summary = $this->repository->create([
            'article_id' => $article->id,
            'content' => 'This is a test summary.',
        ]);

        $this->assertEquals('This is a test summary.', $summary->content);
        $this->assertEquals($article->id, $summary->article_id);
    }

    public function test_update_success(): void
    {
        $model = ArticleSummary::factory()->create([
            'content' => 'Original summary',
        ]);
        $this->repository->update($model, [
            'content' => 'Updated summary',
        ]);

        $updated = ArticleSummary::find($model->id);
        $this->assertEquals('Updated summary', $updated->content);
    }

    public function test_delete_success(): void
    {
        $model = ArticleSummary::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(ArticleSummary::find($model->id));
    }
}
