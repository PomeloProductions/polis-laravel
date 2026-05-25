<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Collection;

use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use App\Models\Wiki\Article;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Collection\CollectionItemRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

final class CollectionItemRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    protected CollectionItemRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new CollectionItemRepository(
            new CollectionItem,
            $this->getGenericLogMock()
        );
    }

    public function test_find_all_success(): void
    {
        CollectionItem::factory()->count(5)->create();
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
        $model = CollectionItem::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        CollectionItem::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        $collection = Collection::factory()->create();
        $item = Article::factory()->create();

        /** @var CollectionItem $model */
        $model = $this->repository->create([
            'item_id' => $item->id,
            'item_type' => 'article',
            'order' => 4,
        ], $collection);

        $this->assertEquals('article', $model->item_type);
        $this->assertEquals($item->id, $model->item_id);
        $this->assertEquals(4, $model->order);
    }

    public function test_update_success(): void
    {
        $model = CollectionItem::factory()->create([
            'item_type' => 'release',
        ]);
        $this->repository->update($model, [
            'item_type' => 'game',
        ]);

        $updated = CollectionItem::find($model->id);
        $this->assertEquals('game', $updated->item_type);
    }

    public function test_delete_success(): void
    {
        $model = CollectionItem::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(CollectionItem::find($model->id));
    }
}
