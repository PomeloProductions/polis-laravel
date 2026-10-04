<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\EmailTemplate;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

final class EmailTemplateUpdateTest extends ApplicationTestCase
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

        $response = $this->json('PUT', $this->route($organization->id, 'welcome'), [
            'subject' => 'Welcome!',
            'body_html' => '<p>Hello</p>',
        ]);

        $response->assertStatus(403);
    }

    public function test_not_admin_blocked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();

        $response = $this->json('PUT', $this->route($organization->id, 'welcome'), [
            'subject' => 'Welcome!',
            'body_html' => '<p>Hello</p>',
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

        $response = $this->json('PUT', $this->route($organization->id, 'welcome'), [
            'subject' => 'Welcome to the platform!',
            'body_html' => '<p>Hello and welcome!</p>',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'key' => 'welcome',
            'subject' => 'Welcome to the platform!',
        ]);
        $response->assertJsonStructure(['key', 'subject', 'body_html', 'source']);
        $this->assertContains($response->json('source'), ['org', 'global', 'default']);
    }

    public function test_update_fails_subject_required(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->route($organization->id, 'welcome'), [
            'body_html' => '<p>Hello</p>',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'subject' => ['The subject field is required.'],
            ],
        ]);
    }

    public function test_update_fails_subject_not_string(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->route($organization->id, 'welcome'), [
            'subject' => 5,
            'body_html' => '<p>Hello</p>',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'subject' => ['The subject must be a string.'],
            ],
        ]);
    }

    public function test_update_fails_subject_too_long(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->route($organization->id, 'welcome'), [
            'subject' => str_repeat('a', 256),
            'body_html' => '<p>Hello</p>',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'subject' => ['The subject may not be greater than 255 characters.'],
            ],
        ]);
    }

    public function test_update_fails_body_html_required(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->route($organization->id, 'welcome'), [
            'subject' => 'Welcome!',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'body_html' => ['The body html field is required.'],
            ],
        ]);
    }

    public function test_update_fails_body_html_not_string(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->route($organization->id, 'welcome'), [
            'subject' => 'Welcome!',
            'body_html' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'body_html' => ['The body html must be a string.'],
            ],
        ]);
    }
}
