<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\Article;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\Wiki\Article;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Feature test for the org-scoped Article ("contract") listing:
 *
 *     GET /v1/organizations/{organization}/articles
 *
 * Modeled on OrganizationOrganizationManagerIndexTest. Verifies the dashboard
 * authorization contract enforced by OrganizationArticlePolicyAbstract:
 *   - platform super-admin -> 200 for any organization,
 *   - org MANAGER/ADMINISTRATOR -> 200, and ONLY sees that org's articles,
 *   - a manager of a DIFFERENT org -> 403 (cross-tenant),
 *   - a non-member / non-admin -> 403,
 *   - an unauthenticated (bypassed-JWT) caller -> 403.
 */
final class OrganizationArticleIndexTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplicationLog();
    }

    private function route(int $organizationId): string
    {
        return '/v1/organizations/'.$organizationId.'/articles';
    }

    /**
     * Create an article owned by the given organization. The package's
     * entity-owner migration backfills owner_id/owner_type from
     * organization_id, but new rows must stamp the owner explicitly so the
     * entity-scoped listing (which filters on the polymorphic owner) matches.
     */
    private function createOrganizationArticle(Organization $organization): Article
    {
        return Article::factory()->create([
            'organization_id' => $organization->id,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $organization = Organization::factory()->create();
        $response = $this->json('GET', $this->route($organization->id));
        $response->assertStatus(403);
    }

    public function test_non_admin_users_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $organization = Organization::factory()->create();
            $response = $this->json('GET', $this->route($organization->id));
            $response->assertStatus(403);
        }
    }

    public function test_manager_of_other_organization_blocked(): void
    {
        $this->actAs(Role::MANAGER);
        $ownOrganization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $ownOrganization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::MANAGER,
        ]);

        $otherOrganization = Organization::factory()->create();

        $response = $this->json('GET', $this->route($otherOrganization->id));
        $response->assertStatus(403);
    }

    public function test_super_admin_sees_index(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $organization = Organization::factory()->create();
        $this->createOrganizationArticle($organization);
        $this->createOrganizationArticle($organization);

        $response = $this->json('GET', $this->route($organization->id));
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 2,
            'current_page' => 1,
        ]);
    }

    public function test_org_manager_sees_only_their_organizations_articles(): void
    {
        $this->actAs(Role::MANAGER);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::MANAGER,
        ]);

        // Two articles owned by the manager's org...
        $this->createOrganizationArticle($organization);
        $this->createOrganizationArticle($organization);

        // ...and articles owned by an unrelated org that must NOT leak.
        $otherOrganization = Organization::factory()->create();
        $this->createOrganizationArticle($otherOrganization);
        $this->createOrganizationArticle($otherOrganization);
        $this->createOrganizationArticle($otherOrganization);

        $response = $this->json('GET', $this->route($organization->id));
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 2,
            'current_page' => 1,
        ]);
    }
}
