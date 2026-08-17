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
 * Feature coverage for the org-scoped push-template revert (destroy) endpoint
 * (PushTemplateControllerAbstract@destroy). Gated by
 * OrganizationPolicy::delete() (ADMINISTRATOR of that org, or SUPER_ADMIN).
 * Idempotent 204.
 */
final class PushTemplateDeleteTest extends ApplicationTestCase
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
        return '/v1/organizations/'.$organizationId.'/push-templates/'.$key;
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $organization = Organization::factory()->create();
        $response = $this->json('DELETE', $this->route($organization->id, 'contact_created'));
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

        $response = $this->json('DELETE', $this->route($organization->id, 'contact_created'));

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

        $response = $this->json('DELETE', $this->route($organization->id, 'contact_created'));

        $response->assertStatus(204);
    }
}
