<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization;

use App\Models\Organization\Organization;
use App\Models\Role;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class OrganizationIndexTest
 */
final class OrganizationIndexTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('GET', '/v1/organizations');
        $response->assertStatus(403);
    }

    public function test_non_admin_users_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $response = $this->json('GET', '/v1/organizations');

            $response->assertStatus(403);
        }
    }

    public function test_get_pagination_empty(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $response = $this->json('GET', '/v1/organizations');

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 0,
            'data' => [],
        ]);
    }

    public function test_get_pagination_result(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        Organization::factory()->count(15)->create();

        // first page
        $response = $this->json('GET', '/v1/organizations');
        $response->assertJson([
            'total' => 15,
            'current_page' => 1,
            'per_page' => 10,
            'from' => 1,
            'to' => 10,
            'last_page' => 2,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new Organization)->toArray()),
                ],
            ]);
        $response->assertStatus(200);

        // second page
        $response = $this->json('GET', '/v1/organizations?page=2');
        $response->assertJson([
            'total' => 15,
            'current_page' => 2,
            'per_page' => 10,
            'from' => 11,
            'to' => 15,
            'last_page' => 2,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new Organization)->toArray()),
                ],
            ]);
        $response->assertStatus(200);

        // page with limit
        $response = $this->json('GET', '/v1/organizations?page=2&limit=5');
        $response->assertJson([
            'total' => 15,
            'current_page' => 2,
            'per_page' => 5,
            'from' => 6,
            'to' => 10,
            'last_page' => 3,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new Organization)->toArray()),
                ],
            ]);
        $response->assertStatus(200);
    }
}
