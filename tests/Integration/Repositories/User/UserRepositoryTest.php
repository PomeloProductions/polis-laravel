<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\User;

use App\Models\Role;
use App\Models\User\User;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\User\UserRepository;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserRepositoryTest
 */
final class UserRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var Hasher
     */
    private $hasher;

    /**
     * @var Repository|CustomMockInterface
     */
    private $config;

    /**
     * @var UserRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->hasher = $this->app->make(Hasher::class);
        $this->config = mock(Repository::class);

        $this->repository = new UserRepository(
            new User,
            $this->getGenericLogMock(),
            $this->hasher,
            $this->config,
        );
    }

    public function test_find_all_success(): void
    {
        User::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_find_all_ordered_by_email(): void
    {
        $userT = User::factory()->create([
            'email' => 't@t.weg',
        ]);
        $userA = User::factory()->create([
            'email' => 'a@t.weg',
        ]);
        $userG = User::factory()->create([
            'email' => 'g@t.weg',
        ]);
        $userZ = User::factory()->create([
            'email' => 'z@t.weg',
        ]);
        $items = $this->repository->findAll([], [], ['email' => 'asc']);
        $this->assertCount(4, $items);
        $this->assertEquals($userA->id, $items[0]->id);
        $this->assertEquals($userG->id, $items[1]->id);
        $this->assertEquals($userT->id, $items[2]->id);
        $this->assertEquals($userZ->id, $items[3]->id);
    }

    public function test_find_or_fail_success(): void
    {
        $model = User::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        User::factory()->create(['id' => 2]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(1);
    }

    public function test_create_success(): void
    {
        $user = $this->repository->create([
            'email' => 'test@test.com',
            'first_name' => 'Kelly Ann Conway',
            'password' => 'Something secure',
        ]);

        $this->assertEquals(1, User::count());
        $this->assertEquals('test@test.com', $user->email);
        $this->assertEquals('Kelly Ann Conway', $user->first_name);
        $this->assertTrue($this->hasher->check('Something secure', $user->password));
    }

    public function test_update_success(): void
    {
        $model = User::factory()->create(['email' => 'butts@butts.com']);
        $this->repository->update($model, [
            'email' => 'bump@butts.com',
            'password' => 'Something secure',
        ]);

        $updated = User::find($model->id);
        $this->assertEquals('bump@butts.com', $updated->email);
        $this->assertTrue($this->hasher->check('Something secure', $updated->password));
    }

    public function test_update_syncs_roles(): void
    {
        $model = User::factory()->create();

        $this->assertCount(0, $model->roles);

        $this->repository->update($model, ['roles' => [Role::SUPER_ADMIN]]);

        $updated = User::find($model->id);
        $this->assertCount(1, $updated->roles);
        $this->assertEquals(Role::SUPER_ADMIN, $updated->roles[0]->id);
    }

    public function test_delete_success(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $this->repository->delete($user);

        $this->assertSoftDeleted('users', ['id' => $userId]);
    }

    public function test_find_by_email_success(): void
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
        ]);

        $result = $this->repository->findByEmail('test@test.com');

        $this->assertEquals($user->id, $result->id);
    }

    public function test_find_by_email_returns_null(): void
    {
        $this->assertNull($this->repository->findByEmail('test@test.com'));
    }

    public function test_find_super_admins_success(): void
    {
        $this->config->shouldReceive('get')->with('mail.from.name')->andReturn('System User');
        $this->config->shouldReceive('get')->with('mail.from.email')->andReturn('test@test.com');

        $this->assertNull(User::whereHas('roles', function ($query) {
            $query->where('role_id', Role::SUPER_ADMIN);
        })->first());

        $users = $this->repository->findSuperAdmins();

        $this->assertNotNull($users[0]);
        $this->assertEquals('System User', $users[0]->first_name);
        $this->assertEquals('test@test.com', $users[0]->email);
    }

    public function test_find_system_users_success(): void
    {
        $user1 = User::factory()->create([
            'email' => 'test@test.com',
            'first_name' => 'System User',
        ]);
        $user1->roles()->attach(Role::SUPER_ADMIN);
        $user2 = User::factory()->create([
            'email' => 'test@test.com',
            'first_name' => 'System User',
        ]);
        $user2->roles()->attach(Role::SUPER_ADMIN);

        User::factory()->count(3)->create();

        $result = $this->repository->findSuperAdmins();

        $this->assertCount(2, $result);
        $this->assertContains($user1->id, $result->pluck('id'));
        $this->assertContains($user2->id, $result->pluck('id'));
    }
}
