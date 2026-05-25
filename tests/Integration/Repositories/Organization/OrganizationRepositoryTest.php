<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Organization;

use App\Models\Organization\Organization;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Organization\OrganizationRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class OrganizationRepositoryTest
 */
final class OrganizationRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var OrganizationRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new OrganizationRepository(
            new Organization,
            $this->getGenericLogMock()
        );
    }

    public function test_find_all_success(): void
    {
        Organization::factory()->count(5)->create();
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
        $model = Organization::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Organization::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        /** @var Organization $model */
        $model = $this->repository->create([
            'name' => 'An Organization',
        ]);

        $this->assertEquals('An Organization', $model->name);
    }

    public function test_update_success(): void
    {
        $model = Organization::factory()->create([
            'name' => 'A Organization',
        ]);
        $this->repository->update($model, [
            'name' => 'An Organization',
        ]);

        /** @var Organization $updated */
        $updated = Organization::find($model->id);
        $this->assertEquals('An Organization', $updated->name);
    }

    public function test_delete_success(): void
    {
        $model = Organization::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(Organization::find($model->id));
    }
}
