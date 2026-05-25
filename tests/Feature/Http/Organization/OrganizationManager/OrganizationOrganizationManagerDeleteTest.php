<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\OrganizationManager;

use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class OrganizationOrganizationManagerDeleteTest
 */
final class OrganizationOrganizationManagerDeleteTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, RolesTesting;

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

    public function test_not_logged_in_user_blocked(): void
    {
        $model = OrganizationManager::factory()->create();
        $this->setupRoute($model->organization_id, $model->id);
        $response = $this->json('DELETE', $this->route);
        $response->assertStatus(403);
    }

    public function test_non_admin_user_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $model = OrganizationManager::factory()->create();
            $this->setupRoute($model->organization_id, $model->id);
            $response = $this->json('DELETE', $this->route);
            $response->assertStatus(403);
        }
    }

    public function test_organization_manager_blocked(): void
    {
        $this->actAs(Role::MANAGER);

        $model = OrganizationManager::factory()->create([
            'role_id' => Role::MANAGER,
            'user_id' => $this->actingAs->id,
        ]);
        $this->setupRoute($model->organization_id, $model->id);

        $response = $this->json('DELETE', $this->route);
        $response->assertStatus(403);
    }

    public function test_delete_single(): void
    {
        $this->actAs(Role::ADMINISTRATOR);

        $model = OrganizationManager::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'user_id' => $this->actingAs->id,
        ]);
        $this->setupRoute($model->organization_id, $model->id);

        $response = $this->json('DELETE', $this->route);

        $response->assertStatus(204);
        $this->assertNull(OrganizationManager::find($model->id));
    }

    public function test_delete_single_invalid_id_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $this->setupRoute(23, 'a');
        $response = $this->json('DELETE', $this->route)
            ->assertSimilarJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_delete_single_not_found_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $this->setupRoute(23, '435');
        $response = $this->json('DELETE', $this->route)
            ->assertSimilarJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }
}
