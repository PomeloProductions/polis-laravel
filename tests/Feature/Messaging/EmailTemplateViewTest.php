<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Messaging;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use Polis\Mail\DefaultEmailTemplates;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Feature coverage for the org-scoped email-template show endpoint
 * (EmailTemplateControllerAbstract@show).
 *
 * show() is gated by OrganizationPolicy::view() (getPolicyModel() =
 * Organization + ACTION_VIEW), i.e. any manager (or higher) of that
 * organization — plus the global SUPER_ADMIN override.
 */
final class EmailTemplateViewTest extends ApplicationTestCase
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
        $response = $this->json('GET', $this->route($organization->id, 'welcome'));
        $response->assertStatus(403);
    }

    public function test_manager_of_a_different_org_is_denied_cross_org(): void
    {
        $this->actAs(Role::MANAGER);
        // Manages some org, but requests a *different* one.
        $this->makeManagedOrg(Role::MANAGER);
        $otherOrganization = Organization::factory()->create();

        $response = $this->json('GET', $this->route($otherOrganization->id, 'welcome'));

        $response->assertStatus(403);
    }

    public function test_org_manager_can_view_a_template_resolved_to_default(): void
    {
        $this->actAs(Role::MANAGER);
        $organization = $this->makeManagedOrg(Role::MANAGER);

        $response = $this->json('GET', $this->route($organization->id, 'welcome'));

        $response->assertStatus(200);
        $response->assertJson([
            'key' => 'welcome',
            'source' => 'default',
            'organization_id' => null,
            'subject' => DefaultEmailTemplates::TEMPLATES['welcome']['subject'],
            'default_subject' => DefaultEmailTemplates::TEMPLATES['welcome']['subject'],
        ]);
    }

    public function test_unknown_key_resolves_to_empty_default_payload(): void
    {
        // The show endpoint never 404s: an unknown key falls all the way
        // through to the in-code default layer, which for a missing key is an
        // empty-string subject/body with source "default".
        $this->actAs(Role::MANAGER);
        $organization = $this->makeManagedOrg(Role::MANAGER);

        $response = $this->json('GET', $this->route($organization->id, 'no-such-template-key'));

        $response->assertStatus(200);
        $response->assertJson([
            'key' => 'no-such-template-key',
            'source' => 'default',
            'subject' => '',
            'body_html' => '',
        ]);
    }
}
