<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories;

use App\Models\Role;
use Mockery;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Repositories\RoleRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for RoleRepository. Aside from instantiation, the only behaviour
 * is the four NotImplemented traits — verify each throws the expected
 * exception.
 */
final class RoleRepositoryTest extends TestCase
{
    private RoleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Role is a plain class fixture — the repository's only contract
        // is BaseRepositoryAbstract behaviour; the actual model class
        // type-hint passes via the fixture's class_alias.
        $modelMock = Mockery::mock(Role::class);
        $this->repository = new RoleRepository($modelMock, $this->getGenericLogMock());
    }

    public function test_repository_extends_base_repository_abstract(): void
    {
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $this->repository);
    }

    public function test_create_throws_not_implemented(): void
    {
        $this->expectException(NotImplementedException::class);
        $this->repository->create();
    }

    public function test_delete_throws_not_implemented(): void
    {
        $this->expectException(NotImplementedException::class);
        $this->repository->delete(Mockery::mock(\Polis\Models\BaseModelAbstract::class));
    }

    public function test_find_or_fail_throws_not_implemented(): void
    {
        $this->expectException(NotImplementedException::class);
        $this->repository->findOrFail(1);
    }

    public function test_update_throws_not_implemented(): void
    {
        $this->expectException(NotImplementedException::class);
        $this->repository->update(Mockery::mock(\Polis\Models\BaseModelAbstract::class), []);
    }
}
