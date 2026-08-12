<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class OrganizationDeleteTest
 */
final class OrganizationDeleteTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    private $route = '/v1/organizations/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $model = Organization::factory()->create();
        $response = $this->json('DELETE', $this->route.$model->id);
        $response->assertStatus(403);
    }

    public function test_non_admin_user_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $model = Organization::factory()->create();
            $response = $this->json('DELETE', $this->route.$model->id);
            $response->assertStatus(403);
        }
    }

    public function test_organization_manager_blocked(): void
    {
        $this->actAs(Role::MANAGER);

        $model = Organization::factory()->create();

        OrganizationManager::factory()->create([
            'role_id' => Role::MANAGER,
            'user_id' => $this->actingAs->id,
            'organization_id' => $model->id,
        ]);

        $response = $this->json('DELETE', $this->route.$model->id);
        $response->assertStatus(403);
    }

    public function test_delete_single(): void
    {
        $this->actAs(Role::ADMINISTRATOR);

        $model = Organization::factory()->create();

        OrganizationManager::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'user_id' => $this->actingAs->id,
            'organization_id' => $model->id,
        ]);

        $response = $this->json('DELETE', $this->route.$model->id);

        $response->assertStatus(204);
        $this->assertNull(Organization::find($model->id));
    }

    public function test_delete_single_invalid_id_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('DELETE', $this->route.'a')
            ->assertSimilarJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_delete_single_not_found_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('DELETE', $this->route.'1')
            ->assertSimilarJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }
}
