<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Messaging;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use Polis\Push\DefaultPushTemplates;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Feature coverage for the org-scoped push-template show endpoint
 * (PushTemplateControllerAbstract@show). Gated by OrganizationPolicy::view()
 * (manager-or-higher of that org, plus the SUPER_ADMIN override).
 */
final class PushTemplateViewTest extends ApplicationTestCase
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

    private function makeManagedOrg(int $roleId): Organization
    {
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => $roleId,
        ]);

        return $organization;
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $organization = Organization::factory()->create();
        $response = $this->json('GET', $this->route($organization->id, 'contact_created'));
        $response->assertStatus(403);
    }

    public function test_manager_of_a_different_org_is_denied_cross_org(): void
    {
        $this->actAs(Role::MANAGER);
        $this->makeManagedOrg(Role::MANAGER);
        $otherOrganization = Organization::factory()->create();

        $response = $this->json('GET', $this->route($otherOrganization->id, 'contact_created'));

        $response->assertStatus(403);
    }

    public function test_org_manager_can_view_a_template_resolved_to_default(): void
    {
        $this->actAs(Role::MANAGER);
        $organization = $this->makeManagedOrg(Role::MANAGER);

        $response = $this->json('GET', $this->route($organization->id, 'contact_created'));

        $response->assertStatus(200);
        $response->assertJson([
            'key' => 'contact_created',
            'source' => 'default',
            'organization_id' => null,
            'title' => DefaultPushTemplates::TEMPLATES['contact_created']['title'],
            'default_title' => DefaultPushTemplates::TEMPLATES['contact_created']['title'],
        ]);
    }

    public function test_unknown_key_resolves_to_empty_default_payload(): void
    {
        $this->actAs(Role::MANAGER);
        $organization = $this->makeManagedOrg(Role::MANAGER);

        $response = $this->json('GET', $this->route($organization->id, 'no-such-push-key'));

        $response->assertStatus(200);
        $response->assertJson([
            'key' => 'no-such-push-key',
            'source' => 'default',
            'title' => '',
            'body' => '',
        ]);
    }
}
