<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Collection;

use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use App\Models\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Collection\CollectionItemRepository;
use Polis\Repositories\Collection\CollectionRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

final class CollectionRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected CollectionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new CollectionRepository(
            new Collection,
            $this->getGenericLogMock(),
            new CollectionItemRepository(
                new CollectionItem,
                $this->getGenericLogMock(),
            ),
        );
    }

    public function test_find_all_success(): void
    {
        Collection::factory()->count(5)->create();
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
        $model = Collection::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Collection::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        $user = User::factory()->create();

        /** @var Collection $model */
        $model = $this->repository->create([
            'name' => 'A Collection',
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);

        $this->assertEquals('A Collection', $model->name);
    }

    public function test_update_success(): void
    {
        $model = Collection::factory()->create([
            'name' => 'a Collection',
        ]);
        $this->repository->update($model, [
            'name' => 'the same',
        ]);

        $updated = Collection::find($model->id);
        $this->assertEquals('the same', $updated->name);
    }

    public function test_update_success_with_new_order(): void
    {
        $model = Collection::factory()->create();

        $collectionItems = CollectionItem::factory()->count(3)->create([
            'collection_id' => $model->id,
        ]);

        /** @var Collection $updated */
        $updated = $this->repository->update($model, [
            'collection_item_order' => [
                $collectionItems[1]->id,
                10000,
                $collectionItems[0]->id,
                $collectionItems[2]->id,
            ],
        ]);

        $this->assertCount(3, $updated->collectionItems);
        $this->assertEquals($collectionItems[1]->id, $updated->collectionItems[0]->id);
        $this->assertEquals($collectionItems[0]->id, $updated->collectionItems[1]->id);
        $this->assertEquals($collectionItems[2]->id, $updated->collectionItems[2]->id);
    }

    public function test_delete_success(): void
    {
        $model = Collection::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(Collection::find($model->id));
    }
}
