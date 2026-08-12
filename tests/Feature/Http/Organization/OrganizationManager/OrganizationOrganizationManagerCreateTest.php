<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\OrganizationManager;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Polis\Events\Organization\OrganizationManagerCreatedEvent;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class OrganizationOrganizationManagerCreateTest
 */
final class OrganizationOrganizationManagerCreateTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    /**
     * @var string
     */
    private $route;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    /**
     * Sets up the proper route for the request
     */
    private function setupRoute(int $organizationId)
    {
        $this->route = '/v1/organizations/'.$organizationId.'/organization-managers';
    }

    public function test_organization_not_found(): void
    {
        $this->setupRoute(4523);
        $response = $this->json('POST', $this->route);
        $response->assertStatus(404);
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $organization = Organization::factory()->create();
        $this->setupRoute($organization->id);
        $response = $this->json('POST', $this->route);
        $response->assertStatus(403);
    }

    public function test_non_admin_users_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $organization = Organization::factory()->create();
            $this->setupRoute($organization->id);
            $response = $this->json('POST', $this->route);

            $response->assertStatus(403);
        }
    }

    public function test_not_user_not_organization_admin_blocked(): void
    {
        $this->actAs(Role::MANAGER);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::MANAGER,
        ]);
        $this->setupRoute($organization->id);
        $response = $this->json('POST', $this->route);
        $response->assertStatus(403);
    }

    public function test_create_successful_with_existing_user(): void
    {
        $this->actAs(Role::ADMINISTRATOR);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $this->setupRoute($organization->id);

        $user = User::factory()->create();

        $properties = [
            'email' => $user->email,
            'role_id' => Role::MANAGER,
        ];

        $dispatcher = mock(Dispatcher::class);
        $this->app->bind(Dispatcher::class, function () use ($dispatcher) {
            return $dispatcher;
        });
        $eventDispatched = false;
        $dispatcher->shouldReceive('dispatch')->with(\Mockery::on(function ($event) use (&$eventDispatched) {

            if ($event instanceof OrganizationManagerCreatedEvent) {
                $eventDispatched = true;
            }

            return true;
        }));

        $response = $this->json('POST', $this->route, $properties);

        $response->assertStatus(201);
        $this->assertTrue($eventDispatched);

        $response->assertJson([
            'user_id' => $user->id,
            'role_id' => Role::MANAGER,
        ]);
    }

    public function test_create_successful_with_new_user(): void
    {
        $this->actAs(Role::ADMINISTRATOR);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $this->setupRoute($organization->id);

        $properties = [
            'email' => 'newuser@test.com',
            'role_id' => Role::MANAGER,
        ];

        $dispatcher = mock(Dispatcher::class);
        $this->app->bind(Dispatcher::class, function () use ($dispatcher) {
            return $dispatcher;
        });
        $eventDispatched = false;
        $dispatcher->shouldReceive('dispatch')->with(\Mockery::on(function ($event) use (&$eventDispatched) {

            if ($event instanceof OrganizationManagerCreatedEvent) {
                $eventDispatched = true;
            }

            return true;
        }));

        $response = $this->json('POST', $this->route, $properties);

        $response->assertStatus(201);
        $this->assertTrue($eventDispatched);

        $response->assertJson([
            'role_id' => Role::MANAGER,
        ]);
        $this->assertNotNull(User::whereEmail('newuser@test.com'));
    }

    public function test_create_fails_missing_required_fields(): void
    {
        $this->actAs(Role::ADMINISTRATOR);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $this->setupRoute($organization->id);

        $response = $this->json('POST', $this->route);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'email' => ['The email field is required.'],
                'role_id' => ['The role id field is required.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_string_fields(): void
    {
        $this->actAs(Role::ADMINISTRATOR);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $this->setupRoute($organization->id);

        $data = [
            'email' => 5435,
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'email' => ['The email must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_numerical_fields(): void
    {
        $this->actAs(Role::ADMINISTRATOR);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $this->setupRoute($organization->id);

        $data = [
            'role_id' => 'weg',
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'role_id' => ['The role id must be an integer.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_email(): void
    {
        $this->actAs(Role::ADMINISTRATOR);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $this->setupRoute($organization->id);

        $data = [
            'email' => str_repeat('a', 10),
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'email' => ['The email must be a valid email address.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_role_id(): void
    {
        $this->actAs(Role::ADMINISTRATOR);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $this->setupRoute($organization->id);

        $data = [
            'role_id' => Role::SUPER_ADMIN,
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'role_id' => ['The selected role id is invalid.'],
            ],
        ]);
    }
}
