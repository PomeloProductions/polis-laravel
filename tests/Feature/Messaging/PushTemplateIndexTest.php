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
 * Feature coverage for the org-scoped push-template index endpoint
 * (Polis\Http\Core\Controllers\Messaging\PushTemplateControllerAbstract@index),
 * routed at `organizations/{organization}/push-templates`. Mirrors
 * EmailTemplateIndexTest — the only structural difference is the payload
 * field names (title/body vs subject/body_html).
 */
final class PushTemplateIndexTest extends ApplicationTestCase
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
        return '/v1/organizations/'.$organizationId.'/push-templates';
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
                    'title',
                    'body',
                    'organization_id',
                    'source',
                    'default_title',
                    'default_body',
                ],
            ],
        ]);

        $keys = array_column($response->json('data'), 'key');
        foreach (array_keys(DefaultPushTemplates::TEMPLATES) as $expectedKey) {
            $this->assertContains($expectedKey, $keys);
        }

        $contactCreated = collect($response->json('data'))->firstWhere('key', 'contact_created');
        $this->assertSame('default', $contactCreated['source']);
        $this->assertNull($contactCreated['organization_id']);
        $this->assertSame(
            DefaultPushTemplates::TEMPLATES['contact_created']['title'],
            $contactCreated['title'],
        );
    }
}
