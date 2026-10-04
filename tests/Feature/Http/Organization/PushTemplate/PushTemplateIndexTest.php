<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\PushTemplate;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

final class PushTemplateIndexTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    private function route(int $orgId): string
    {
        return '/v1/organizations/'.$orgId.'/push-templates';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->json('GET', $this->route($organization->id));

        $response->assertStatus(403);
    }

    public function test_not_admin_blocked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();

        $response = $this->json('GET', $this->route($organization->id));

        $response->assertStatus(403);
    }

    /**
     * The IndexRequest guards against the Organization policy, whose `all`
     * ability (listing is a cross-org capability) is reserved for super
     * admins — so an org administrator is NOT authorized to list.
     */
    public function test_org_admin_blocked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('GET', $this->route($organization->id));

        $response->assertStatus(403);
    }

    public function test_index_successful(): void
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
        // The in-code defaults are always present in the merged list.
        $response->assertJsonFragment(['key' => 'contact_created']);
        $this->assertContains($response->json('data.0.source'), ['org', 'global', 'default']);
    }
}
