<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories;

use App\Models\Feature;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\FeatureRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ResourceRepositoryTest
 */
final class FeatureRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected FeatureRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new FeatureRepository(
            new Feature,
            $this->getGenericLogMock()
        );

        Feature::all()->each(fn (Feature $i) => $i->delete());
    }

    public function test_find_all_success(): void
    {
        Feature::factory()->count(5)->create();
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
        $model = Feature::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Feature::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        /** @var Feature $feature */
        $feature = $this->repository->create([
            'name' => 'A Feature',
        ]);

        $this->assertEquals('A Feature', $feature->name);
    }

    public function test_update_success(): void
    {
        $model = Feature::factory()->create([
            'name' => 'a code',
        ]);
        $this->repository->update($model, [
            'name' => 'the same',
        ]);

        $updated = Feature::find($model->id);
        $this->assertEquals('the same', $updated->name);
    }

    public function test_delete_success(): void
    {
        $model = Feature::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(Feature::find($model->id));
    }
}
