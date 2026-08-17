<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Messaging;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Feature coverage for the org-scoped email-template revert (destroy) endpoint
 * (EmailTemplateControllerAbstract@destroy).
 *
 * destroy() is gated by OrganizationPolicy::delete() (ACTION_DELETE), which
 * requires ADMINISTRATOR of that organization (or the SUPER_ADMIN override).
 * The endpoint is idempotent: reverting a key with no override still returns
 * 204.
 */
final class EmailTemplateDeleteTest extends ApplicationTestCase
{
    use MocksApplicationLog;
    use RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplicationLog();
    }

    private function route(int $organizationId, string $key): string
    {
        return '/v1/organizations/'.$organizationId.'/email-templates/'.$key;
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $organization = Organization::factory()->create();
        $response = $this->json('DELETE', $this->route($organization->id, 'welcome'));
        $response->assertStatus(403);
    }

    public function test_org_manager_cannot_revert_requires_administrator(): void
    {
        $this->actAs(Role::MANAGER);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::MANAGER,
        ]);

        $response = $this->json('DELETE', $this->route($organization->id, 'welcome'));

        $response->assertStatus(403);
    }

    public function test_org_administrator_revert_is_idempotent_no_content(): void
    {
        $this->actAs(Role::ADMINISTRATOR);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        // No org override exists for this key; revert is still a clean 204.
        $response = $this->json('DELETE', $this->route($organization->id, 'welcome'));

        $response->assertStatus(204);
    }
}
