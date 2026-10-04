<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\PushTemplate;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

final class PushTemplateUpdateTest extends ApplicationTestCase
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

        $response = $this->json('PUT', $this->route($organization->id, 'contact_created'), [
            'title' => 'New Contact!',
            'body' => 'Someone wants to connect.',
        ]);

        $response->assertStatus(403);
    }

    public function test_not_admin_blocked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();

        $response = $this->json('PUT', $this->route($organization->id, 'contact_created'), [
            'title' => 'New Contact!',
            'body' => 'Someone wants to connect.',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_successful(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->route($organization->id, 'contact_created'), [
            'title' => 'You have a new contact!',
            'body' => 'Someone wants to connect with you.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'key' => 'contact_created',
            'title' => 'You have a new contact!',
        ]);
        $response->assertJsonStructure(['key', 'title', 'body', 'source']);
        $this->assertContains($response->json('source'), ['org', 'global', 'default']);
    }

    public function test_update_fails_title_required(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->route($organization->id, 'contact_created'), [
            'body' => 'Someone wants to connect.',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'title' => ['The title field is required.'],
            ],
        ]);
    }

    public function test_update_fails_title_not_string(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->route($organization->id, 'contact_created'), [
            'title' => 5,
            'body' => 'Someone wants to connect.',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'title' => ['The title must be a string.'],
            ],
        ]);
    }

    public function test_update_fails_title_too_long(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->route($organization->id, 'contact_created'), [
            'title' => str_repeat('a', 256),
            'body' => 'Someone wants to connect.',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'title' => ['The title may not be greater than 255 characters.'],
            ],
        ]);
    }

    public function test_update_fails_body_required(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->route($organization->id, 'contact_created'), [
            'title' => 'New Contact!',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'body' => ['The body field is required.'],
            ],
        ]);
    }

    public function test_update_fails_body_not_string(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->route($organization->id, 'contact_created'), [
            'title' => 'New Contact!',
            'body' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'body' => ['The body must be a string.'],
            ],
        ]);
    }
}
