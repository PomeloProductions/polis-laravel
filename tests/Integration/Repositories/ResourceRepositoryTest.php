<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories;

use App\Models\Resource;
use App\Models\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\ResourceRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ResourceRepositoryTest
 */
final class ResourceRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var ResourceRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        User::unsetEventDispatcher();

        $this->repository = new ResourceRepository(
            new Resource,
            $this->getGenericLogMock()
        );
    }

    public function test_find_all_success(): void
    {
        foreach (Resource::all() as $resource) {
            $resource->delete();
        }

        Resource::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        foreach (Resource::all() as $resource) {
            $resource->delete();
        }

        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_find_or_fail_success(): void
    {
        $model = Resource::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Resource::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var resource $resource */
        $resource = $this->repository->create([
            'content' => 'Some Content',
            'resource_id' => $user->id,
            'resource_type' => 'user',
        ]);

        $this->assertEquals('user', $resource->resource_type);
        $this->assertEquals($user->id, $resource->resource_id);
        $this->assertEquals('Some Content', $resource->content);
    }

    public function test_update_success(): void
    {
        $model = Resource::factory()->create([
            'content' => 'a code',
        ]);
        $this->repository->update($model, [
            'content' => 'the same',
        ]);

        $updated = Resource::find($model->id);
        $this->assertEquals('the same', $updated->content);
    }

    public function test_delete_success(): void
    {
        $model = Resource::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(Resource::find($model->id));
    }
}
