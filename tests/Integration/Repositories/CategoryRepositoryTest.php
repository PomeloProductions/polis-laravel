<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\CategoryRepository;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class RoleRepositoryTest
 */
final class CategoryRepositoryTest extends ApplicationTestCase
{
    
    /**
     * @var CategoryRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new CategoryRepository(new Category, $this->getGenericLogMock());
    }

    public function test_find_all_success(): void
    {
        Category::factory()->count(5)->create();
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
        $model = Category::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Category::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        /** @var Category $category */
        $category = $this->repository->create([
            'name' => 'A Category',
        ]);

        $this->assertEquals('A Category', $category->name);
    }

    public function test_update_success(): void
    {
        $model = Category::factory()->create([
            'name' => 'An Category',
        ]);
        $this->repository->update($model, [
            'name' => 'A Category',
        ]);

        $updated = Category::find($model->id);
        $this->assertEquals('A Category', $updated->name);
    }

    public function test_delete_success(): void
    {
        $model = Category::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(Category::find($model->id));
    }
}
