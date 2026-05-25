<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleModification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Wiki\ArticleModificationRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ArticleModificationRepositoryTest
 */
final class ArticleModificationRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    protected ArticleModificationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new ArticleModificationRepository(
            new ArticleModification,
            $this->getGenericLogMock(),
        );
    }

    public function test_find_all_success(): void
    {
        ArticleModification::factory()->count(5)->create();
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
        $model = ArticleModification::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        ArticleModification::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        $article = Article::factory()->create();

        /** @var ArticleModification $articleModification */
        $articleModification = $this->repository->create([
            'content' => 'Some Content',
            'action' => 'add',
            'start_position' => 0,
        ], $article);

        $this->assertEquals($articleModification->article_id, $article->id);
        $this->assertEquals($articleModification->content, 'Some Content');
        $this->assertEquals($articleModification->action, 'add');
        $this->assertEquals($articleModification->start_position, 0);
    }

    public function test_update_success(): void
    {
        $model = ArticleModification::factory()->create([
            'start_position' => 0,
        ]);
        $this->repository->update($model, [
            'start_position' => 10,
        ]);

        $updated = ArticleModification::find($model->id);
        $this->assertEquals($updated->start_position, 10);
    }

    public function test_delete_success(): void
    {
        $model = ArticleModification::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(ArticleModification::find($model->id));
    }
}
