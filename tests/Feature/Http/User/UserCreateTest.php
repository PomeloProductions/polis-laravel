<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User;

use App\Models\Role;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserCreateTest
 */
final class UserCreateTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        User::unsetEventDispatcher();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_non_super_admin_user_blocked(): void
    {
        $this->actAsUser();

        $response = $this->json('POST', $this->path, [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('POST', $this->path, [
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('John', $user->first_name);
        $this->assertEquals('Doe', $user->last_name);
    }

    public function test_create_successful_minimal_fields(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('POST', $this->path, [
            'email' => 'minimal@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'minimal@example.com')->first();
        $this->assertNotNull($user);
    }

    public function test_create_fails_missing_email(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('POST', $this->path, [
            'password' => 'password123',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'email' => ['The email field is required.'],
            ],
        ]);
    }

    public function test_create_fails_missing_password(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('POST', $this->path, [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'password' => ['The password field is required.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_email(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('POST', $this->path, [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'email' => ['The email must be a valid email address.'],
            ],
        ]);
    }

    public function test_create_fails_duplicate_email(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->json('POST', $this->path, [
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'email' => ['The email has already been taken.'],
            ],
        ]);
    }

    public function test_create_fails_password_too_short(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('POST', $this->path, [
            'email' => 'test@example.com',
            'password' => '12345',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'password' => ['The password must be at least 6 characters.'],
            ],
        ]);
    }

    public function test_create_successful_with_roles(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();

        $response = $this->json('POST', $this->path, [
            'email' => 'withroles@example.com',
            'password' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'roles' => [$role1->id, $role2->id],
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'withroles@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole($role1->id));
        $this->assertTrue($user->hasRole($role2->id));
    }

    public function test_create_fails_invalid_role_id(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('POST', $this->path, [
            'email' => 'test@example.com',
            'password' => 'password123',
            'roles' => ['not-an-integer'],
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'roles.0' => ['The roles.0 must be an integer.'],
            ],
        ]);
    }

    public function test_create_fails_non_existent_role(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('POST', $this->path, [
            'email' => 'test@example.com',
            'password' => 'password123',
            'roles' => [99999],
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'roles.0' => ['The selected roles.0 is invalid.'],
            ],
        ]);
    }
}
