<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories;

use App\Models\Role;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\RoleRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class RoleRepositoryTest
 */
final class RoleRepositoryTest extends TestCase
{
    use DatabaseSetupTrait;

    /**
     * @var RoleRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new RoleRepository(new Role, $this->getGenericLogMock());
    }

    public function test_find_all_success(): void
    {
        $items = $this->repository->findAll([], [], [], [], 0);
        $this->assertCount(Role::count(), $items);
    }

    public function test_update(): void
    {
        $this->expectException(NotImplementedException::class);
        $this->repository->update(new Role, []);
    }

    public function test_delete(): void
    {
        $this->expectException(NotImplementedException::class);
        $this->repository->delete(new Role);
    }

    public function test_find_or_fail(): void
    {
        $this->expectException(NotImplementedException::class);
        $this->repository->findOrFail(1);
    }

    public function test_create(): void
    {
        $this->expectException(NotImplementedException::class);
        $this->repository->create([]);
    }
}
