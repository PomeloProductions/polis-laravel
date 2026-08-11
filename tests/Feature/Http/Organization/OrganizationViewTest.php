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
 * Class OrganizationViewTest
 */
final class OrganizationViewTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $model = Organization::factory()->create();
        $response = $this->json('GET', '/v1/organizations/'.$model->id);
        $response->assertStatus(403);
    }

    public function test_non_admin_users_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $model = Organization::factory()->create();
            $response = $this->json('GET', '/v1/organizations/'.$model->id);
            $response->assertStatus(403);
        }
    }

    public function test_get_single_success(): void
    {
        $this->actAs(Role::MANAGER);
        /** @var Organization $model */
        $model = Organization::factory()->create([
            'id' => 1,
        ]);
        OrganizationManager::factory()->create([
            'user_id' => $this->actingAs->id,
            'role_id' => Role::MANAGER,
            'organization_id' => $model->id,
        ]);

        $response = $this->json('GET', '/v1/organizations/1');

        $response->assertStatus(200);
        $response->assertJson($model->toArray());
    }

    public function test_get_single_not_found_fails(): void
    {
        $this->actAs(Role::APP_USER);
        $response = $this->json('GET', '/v1/organizations/1')
            ->assertSimilarJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_get_single_invalid_id_fails(): void
    {
        $this->actAs(Role::APP_USER);
        $response = $this->json('GET', '/v1/organizations/a')
            ->assertSimilarJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }
}
