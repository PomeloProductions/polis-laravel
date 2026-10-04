<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\PushTemplate;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

final class PushTemplateDeleteTest extends ApplicationTestCase
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
        return '/v1/organizations/'.$orgId.'/push-templates/'.$key;
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->json('DELETE', $this->route($organization->id, 'contact_created'));

        $response->assertStatus(403);
    }

    public function test_not_admin_blocked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();

        $response = $this->json('DELETE', $this->route($organization->id, 'contact_created'));

        $response->assertStatus(403);
    }

    public function test_delete_reverts_org_override(): void
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
        $this->json('PUT', $this->route($organization->id, 'contact_created'), [
            'title' => 'Custom Title',
            'body' => 'Custom body',
        ])->assertStatus(200);

        // Confirm the override is in place before deleting.
        $this->json('GET', $this->route($organization->id, 'contact_created'))
            ->assertJsonFragment(['source' => 'org']);

        $response = $this->json('DELETE', $this->route($organization->id, 'contact_created'));

        $response->assertStatus(204);
        $response->assertNoContent();

        // After deletion the lookup falls through to the in-code default.
        $this->json('GET', $this->route($organization->id, 'contact_created'))
            ->assertStatus(200)
            ->assertJsonFragment([
                'key' => 'contact_created',
                'source' => 'default',
            ]);
    }

    public function test_delete_is_idempotent_for_non_overridden_key(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('DELETE', $this->route($organization->id, 'contact_created'));

        $response->assertStatus(204);
        $response->assertNoContent();
    }
}
