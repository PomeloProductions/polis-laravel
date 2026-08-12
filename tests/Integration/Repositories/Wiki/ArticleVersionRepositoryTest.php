<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use App\Models\Wiki\ArticleVersion;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Events\Article\ArticleVersionCreatedEvent;
use Polis\Repositories\Wiki\ArticleVersionRepository;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ArticleVersionRepositoryTest
 */
final class ArticleVersionRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var ArticleVersionRepository
     */
    protected $repository;

    /**
     * @var Dispatcher|CustomMockInterface
     */
    private $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->dispatcher = mock(Dispatcher::class);

        $this->repository = new ArticleVersionRepository(
            new ArticleVersion,
            $this->getGenericLogMock(),
            $this->dispatcher,
        );
    }

    public function test_find_all_success(): void
    {
        ArticleVersion::factory()->count(5)->create();
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
        $model = ArticleVersion::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        ArticleVersion::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        $article = Article::factory()->create();
        $iteration = ArticleIteration::factory()->create();

        $this->dispatcher->shouldReceive('dispatch')->once()->with(\Mockery::on(function (ArticleVersionCreatedEvent $event) {
            return true;
        }));

        /** @var ArticleVersion $articleVersion */
        $articleVersion = $this->repository->create([
            'article_iteration_id' => $iteration->id,
        ], $article);

        $this->assertEquals($articleVersion->article_id, $article->id);
        $this->assertEquals($articleVersion->article_iteration_id, $iteration->id);
    }

    public function test_update_success(): void
    {
        $model = ArticleVersion::factory()->create([
            'name' => null,
        ]);
        $this->repository->update($model, [
            'name' => '1.0.0',
        ]);

        $updated = ArticleVersion::find($model->id);
        $this->assertEquals('1.0.0', $updated->name);
    }

    public function test_delete_success(): void
    {
        $model = ArticleVersion::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(ArticleVersion::find($model->id));
    }
}
