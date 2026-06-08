<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\User;

use App\Models\Role;
use App\Models\User\User;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Polis\Repositories\User\UserRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for UserRepository — password hashing on create/update, role
 * sync, findByEmail, and the findSuperAdmins fallback that bootstraps a
 * superadmin when none exists.
 */
final class UserRepositoryTest extends TestCase
{
    private function buildUserMock(int $id = 1)
    {
        $mock = Mockery::mock(User::class);
        $mock->shouldReceive('setAttribute');
        $mock->shouldReceive('getAttribute')->andReturn($id);
        $mock->wasRecentlyCreated = true;

        return $mock;
    }

    public function test_create_hashes_password_before_persisting(): void
    {
        $modelMock = $this->buildUserMock();
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertSame('hashed', $data['password']);

                return $modelMock;
            });
        $modelMock->shouldReceive('roles->sync')->once()->with([1, 2]);

        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldReceive('make')->once()->with('plaintext')->andReturn('hashed');

        $config = Mockery::mock(Config::class);

        $repo = new UserRepository($modelMock, $this->getGenericLogMock(), $hasher, $config);
        $repo->create([
            'password' => 'plaintext',
            'roles' => [1, 2],
        ]);
    }

    public function test_create_without_password_skips_hashing(): void
    {
        $modelMock = $this->buildUserMock();
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('roles->sync')->once()->with([]);

        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldNotReceive('make');

        $config = Mockery::mock(Config::class);

        $repo = new UserRepository($modelMock, $this->getGenericLogMock(), $hasher, $config);
        $repo->create();
    }

    public function test_update_hashes_password_and_syncs_roles_when_passed(): void
    {
        $modelMock = $this->buildUserMock();
        $modelMock->shouldReceive('update')
            ->once()
            ->andReturnUsing(function ($data) {
                $this->assertSame('hashed', $data['password']);

                return true;
            });
        $modelMock->shouldReceive('roles->sync')->once()->with([7]);

        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldReceive('make')->once()->with('newpass')->andReturn('hashed');

        $config = Mockery::mock(Config::class);

        $repo = new UserRepository($modelMock, $this->getGenericLogMock(), $hasher, $config);
        $repo->update($modelMock, ['password' => 'newpass', 'roles' => [7]]);
    }

    public function test_update_without_password_does_not_hash(): void
    {
        $modelMock = $this->buildUserMock();
        $modelMock->shouldReceive('update')->once()->andReturn(true);

        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldNotReceive('make');

        $config = Mockery::mock(Config::class);

        $repo = new UserRepository($modelMock, $this->getGenericLogMock(), $hasher, $config);
        $repo->update($modelMock, ['first_name' => 'Ada']);
    }

    public function test_find_by_email_uses_where_filter(): void
    {
        $expected = new User;
        $expected->forceFill(['email' => 'a@b.c']);

        $modelMock = Mockery::mock(User::class);
        $modelMock->shouldReceive('where')->once()->with('email', 'a@b.c')->andReturnSelf();
        $modelMock->shouldReceive('first')->once()->andReturn($expected);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $hasher = Mockery::mock(Hasher::class);
        $config = Mockery::mock(Config::class);

        $repo = new UserRepository($modelMock, $this->getGenericLogMock(), $hasher, $config);
        $this->assertSame($expected, $repo->findByEmail('a@b.c'));
    }

    public function test_find_super_admins_returns_existing_admins_when_any(): void
    {
        $admin = new User;
        $admin->forceFill(['email' => 'admin@x.test']);

        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('get')->once()->andReturn(new EloquentCollection([$admin]));

        $modelMock = Mockery::mock(User::class);
        $modelMock->shouldReceive('whereHas')
            ->once()
            ->andReturnUsing(function ($relation, $callback) use ($queryMock) {
                // Run the closure so the test verifies it calls
                // ->where('role_id', SUPER_ADMIN)
                $innerQuery = Mockery::mock();
                $innerQuery->shouldReceive('where')->once()->with('role_id', Role::SUPER_ADMIN);
                $callback($innerQuery);

                return $queryMock;
            });
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $hasher = Mockery::mock(Hasher::class);
        $config = Mockery::mock(Config::class);

        $repo = new UserRepository($modelMock, $this->getGenericLogMock(), $hasher, $config);
        $result = $repo->findSuperAdmins();
        $this->assertCount(1, $result);
    }

    public function test_find_super_admins_bootstraps_when_none_exist(): void
    {
        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('get')->once()->andReturn(new EloquentCollection); // empty

        $modelMock = Mockery::mock(User::class);
        $modelMock->wasRecentlyCreated = false;
        $modelMock->shouldReceive('whereHas')->once()->andReturn($queryMock);
        // create() path
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);
        $modelMock->shouldReceive('save')->once();
        $modelMock->shouldReceive('roles->sync')->once()->with([]);
        $modelMock->shouldReceive('roles->attach')->once()->with(Role::SUPER_ADMIN);

        $hasher = Mockery::mock(Hasher::class);

        $config = Mockery::mock(Config::class);
        $config->shouldReceive('get')->with('mail.from.name')->andReturn('Polis Admin');
        $config->shouldReceive('get')->with('mail.from.email')->andReturn('admin@x');

        $repo = new UserRepository($modelMock, $this->getGenericLogMock(), $hasher, $config);
        $result = $repo->findSuperAdmins();

        $this->assertCount(1, $result);
    }
}
