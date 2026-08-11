<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Messaging;

use App\Models\Messaging\Thread;
use App\Models\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\Messaging\ThreadRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ThreadRepositoryTest
 */
final class ThreadRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var ThreadRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new ThreadRepository(
            new Thread,
            $this->getGenericLogMock(),
        );
    }

    public function test_find_all_success(): void
    {
        foreach (Thread::all() as $resource) {
            $resource->delete();
        }

        Thread::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        foreach (Thread::all() as $resource) {
            $resource->delete();
        }

        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_find_or_fail_success(): void
    {
        $model = Thread::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Thread::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        $users = User::factory()->count(2)->create();

        /** @var Thread $thread */
        $thread = $this->repository->create([
            'users' => $users->pluck('id'),
        ]);

        $this->assertCount(2, $thread->users);
    }

    public function test_update_fails(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->update(new Thread, []);
    }

    public function test_delete_success(): void
    {
        $model = Thread::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(Thread::find($model->id));
    }
}
