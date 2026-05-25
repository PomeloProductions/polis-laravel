<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Organization;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Organization\OrganizationManagerRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class OrganizationManagerRepositoryTest
 */
final class OrganizationManagerRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var OrganizationManagerRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new OrganizationManagerRepository(
            new OrganizationManager,
            $this->getGenericLogMock()
        );
    }

    public function test_find_all_success(): void
    {
        OrganizationManager::factory()->count(5)->create();
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
        $model = OrganizationManager::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        OrganizationManager::factory()->create(['id' => 3452]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(546);
    }

    public function test_create_success(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        /** @var OrganizationManager $model */
        $model = $this->repository->create([
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ], $organization);

        $this->assertEquals($organization->id, $model->organization_id);
        $this->assertEquals($user->id, $model->user_id);
        $this->assertEquals(Role::ADMINISTRATOR, $model->role_id);
    }

    public function test_update_success(): void
    {
        $model = OrganizationManager::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $this->repository->update($model, [
            'role_id' => Role::MANAGER,
        ]);

        /** @var OrganizationManager $updated */
        $updated = OrganizationManager::find($model->id);
        $this->assertEquals(Role::MANAGER, $updated->role_id);
    }

    public function test_delete_success(): void
    {
        $model = OrganizationManager::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(OrganizationManager::find($model->id));
    }
}
