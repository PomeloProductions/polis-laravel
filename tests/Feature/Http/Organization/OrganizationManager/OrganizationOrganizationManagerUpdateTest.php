<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\OrganizationManager;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class OrganizationUpdateTest
 */
final class OrganizationOrganizationManagerUpdateTest extends ApplicationTestCase
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
     *
     * @param  int  $organizationManagerId
     */
    private function setupRoute(int $organizationId, $organizationManagerId)
    {
        $this->route = '/v1/organizations/'.$organizationId.'/organization-managers/'.$organizationManagerId;
    }

    public function test_organization_not_found(): void
    {
        $this->setupRoute(4523, 345);
        $response = $this->json('PUT', $this->route);
        $response->assertStatus(404);
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $model = OrganizationManager::factory()->create();
        $this->setupRoute($model->organization_id, $model->id);
        $response = $this->json('PUT', $this->route);
        $response->assertStatus(403);
    }

    public function test_non_admin_users_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $model = OrganizationManager::factory()->create();
            $this->setupRoute($model->organization_id, $model->id);
            $response = $this->json('PUT', $this->route);

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
        $model = OrganizationManager::factory()->create();
        $this->setupRoute($model->organization_id, $model->id);
        $response = $this->json('PUT', $this->route);
        $response->assertStatus(403);
    }

    public function test_update_successful(): void
    {
        $this->actAs(Role::ADMINISTRATOR);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $model = OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'role_id' => Role::MANAGER,
        ]);
        $this->setupRoute($model->organization_id, $model->id);

        $properties = [
            'role_id' => Role::ADMINISTRATOR,
        ];

        $response = $this->json('PUT', $this->route, $properties);

        $response->assertStatus(200);

        /** @var OrganizationManager $updated */
        $updated = OrganizationManager::find($model->id);

        $this->assertEquals(Role::ADMINISTRATOR, $updated->role_id);
    }

    public function test_update_fails_missing_required_fields(): void
    {
        $this->actAs(Role::ADMINISTRATOR);
        $model = OrganizationManager::factory()->create([
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $this->setupRoute($model->organization_id, $model->id);

        $response = $this->json('PUT', $this->route);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'role_id' => ['The role id field is required.'],
            ],
        ]);
    }

    public function test_update_fails_invalid_numerical_fields(): void
    {
        $this->actAs(Role::ADMINISTRATOR);
        $model = OrganizationManager::factory()->create([
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $this->setupRoute($model->organization_id, $model->id);

        $data = [
            'role_id' => 'weg',
        ];

        $response = $this->json('PUT', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'role_id' => ['The role id must be an integer.'],
            ],
        ]);
    }

    public function test_update_fails_invalid_role_id(): void
    {
        $this->actAs(Role::ADMINISTRATOR);
        $model = OrganizationManager::factory()->create([
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $this->setupRoute($model->organization_id, $model->id);

        $data = [
            'role_id' => Role::SUPER_ADMIN,
        ];

        $response = $this->json('PUT', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'role_id' => ['The selected role id is invalid.'],
            ],
        ]);
    }
}
