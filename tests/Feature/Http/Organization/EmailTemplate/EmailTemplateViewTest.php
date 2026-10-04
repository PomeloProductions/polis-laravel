<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\EmailTemplate;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

final class EmailTemplateViewTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    private function route(int $orgId, string $key): string
    {
        return '/v1/organizations/'.$orgId.'/email-templates/'.$key;
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->json('GET', $this->route($organization->id, 'welcome'));

        $response->assertStatus(403);
    }

    public function test_not_admin_blocked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();

        $response = $this->json('GET', $this->route($organization->id, 'welcome'));

        $response->assertStatus(403);
    }

    public function test_view_successful_falls_back_to_default(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('GET', $this->route($organization->id, 'welcome'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'key',
            'subject',
            'body_html',
            'organization_id',
            'source',
            'default_subject',
            'default_body_html',
        ]);
        $response->assertJsonFragment([
            'key' => 'welcome',
            'source' => 'default',
        ]);
    }

    public function test_view_successful_returns_org_override(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        // Seed an org-scoped override via the update endpoint.
        $this->json('PUT', $this->route($organization->id, 'welcome'), [
            'subject' => 'Custom Welcome',
            'body_html' => '<p>Custom body</p>',
        ])->assertStatus(200);

        $response = $this->json('GET', $this->route($organization->id, 'welcome'));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'key' => 'welcome',
            'subject' => 'Custom Welcome',
            'organization_id' => $organization->id,
            'source' => 'org',
        ]);
    }
}
