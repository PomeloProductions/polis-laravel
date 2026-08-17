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
 * Feature coverage for the org-scoped email-template index endpoint
 * (Polis\Http\Core\Controllers\Messaging\EmailTemplateControllerAbstract@index),
 * now routed into the dummy consumer app at
 * `organizations/{organization}/email-templates`.
 *
 * NOTE on authorization: the request's getPolicyModel() is Organization, so
 * the Gate resolves the OrganizationPolicy for the `all` ability (index). The
 * package's OrganizationPolicy::all() denies everyone except the SUPER_ADMIN
 * (granted globally by BasePolicyAbstract::before). These tests assert that
 * real, wired behaviour rather than the docblock's aspirational manager-level
 * gating.
 */
final class EmailTemplateIndexTest extends ApplicationTestCase
{
    use MocksApplicationLog;
    use RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplicationLog();
    }

    private function route(int $organizationId): string
    {
        return '/v1/organizations/'.$organizationId.'/email-templates';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $organization = Organization::factory()->create();
        $response = $this->json('GET', $this->route($organization->id));
        $response->assertStatus(403);
    }

    public function test_non_super_admin_users_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $organization = Organization::factory()->create();
            OrganizationManager::factory()->create([
                'organization_id' => $organization->id,
                'user_id' => $this->actingAs->id,
                'role_id' => $role,
            ]);
            $response = $this->json('GET', $this->route($organization->id));
            $response->assertStatus(403);
        }
    }

    public function test_super_admin_lists_all_known_templates(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $organization = Organization::factory()->create();

        $response = $this->json('GET', $this->route($organization->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'key',
                    'subject',
                    'body_html',
                    'organization_id',
                    'source',
                    'default_subject',
                    'default_body_html',
                ],
            ],
        ]);

        // Every in-code default key is present, sorted, and resolves to the
        // in-code default when no DB override exists.
        $keys = array_column($response->json('data'), 'key');
        foreach (array_keys(DefaultEmailTemplates::TEMPLATES) as $expectedKey) {
            $this->assertContains($expectedKey, $keys);
        }

        $welcome = collect($response->json('data'))->firstWhere('key', 'welcome');
        $this->assertSame('default', $welcome['source']);
        $this->assertNull($welcome['organization_id']);
        $this->assertSame(
            DefaultEmailTemplates::TEMPLATES['welcome']['subject'],
            $welcome['subject'],
        );
    }
}
