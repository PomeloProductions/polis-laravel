<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Wiki;

use App\Models\User\User;
use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\Wiki\ArticleIterationRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ArticleIterationRepositoryTest
 */
final class ArticleIterationRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    private ArticleIterationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new ArticleIterationRepository(
            new ArticleIteration,
            $this->getGenericLogMock(),
        );
    }

    public function test_delete_throws_exception(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->delete(new ArticleIteration);
    }

    public function test_update_throws_exception(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->update(new ArticleIteration, []);
    }

    public function test_find_or_fail_success(): void
    {
        $model = ArticleIteration::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        ArticleIteration::factory()->create(['id' => 2]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(1);
    }

    public function test_find_all_success(): void
    {
        ArticleIteration::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_create_success(): void
    {
        $article = Article::factory()->create();
        $user = User::factory()->create();

        /** @var ArticleIteration $model */
        $model = $this->repository->create([
            'content' => 'hello',
            'created_by_id' => $user->id,
        ], $article);

        $this->assertEquals('hello', $model->content);
        $this->assertEquals($article->id, $model->article_id);
        $this->assertEquals($user->id, $model->created_by_id);
    }
}
