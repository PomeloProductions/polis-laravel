<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Wiki;

use App\Models\User\User;
use App\Models\Wiki\Article;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Contracts\Repositories\Statistic\StatisticRepositoryContract;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\Wiki\ArticleRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ArticleRepositoryTest
 */
final class ArticleRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var ArticleRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new ArticleRepository(
            new Article,
            $this->getGenericLogMock(),
            app(StatisticRepositoryContract::class)
        );
    }

    public function test_delete_throws_exception(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->delete(new Article);
    }

    public function test_find_all_success(): void
    {
        Article::factory()->count(5)->create();
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
        $model = Article::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Article::factory()->create(['id' => 2]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(1);
    }

    public function test_create_success(): void
    {
        $user = User::factory()->create();

        /** @var Article $article */
        $article = $this->repository->create([
            'title' => 'An Article',
            'created_by_id' => $user->id,
        ]);

        $this->assertEquals('An Article', $article->title);
        $this->assertEquals($user->id, $article->created_by_id);
    }

    public function test_update_success(): void
    {
        $model = Article::factory()->create([
            'title' => 'Ann Article',
        ]);
        $this->repository->update($model, [
            'title' => 'An Article',
        ]);

        $updated = Article::find($model->id);
        $this->assertEquals('An Article', $updated->title);
    }
}
