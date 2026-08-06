<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use App\Models\Role;
use Mockery;
use Polis\Contracts\Models\BelongsToOrganizationContract;
use Polis\Tests\Fixtures\Models\Organization;
use Polis\Tests\Fixtures\Policies\Wiki\OrganizationArticlePolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for OrganizationArticlePolicyAbstract — the org-scoped Article
 * ("contract") policy powering GET /organizations/{organization}/articles.
 *
 * It inherits every gate from BaseBelongsToOrganizationPolicyAbstract, so the
 * relevant guarantees are:
 *   - super-admins pass before() for ANY organization,
 *   - all()/view() require the caller to manage the organization,
 *   - view() also asserts the Article's organization_id matches the route org
 *     (cross-tenant boundary), and returns false for a different org.
 *
 * This is deliberately distinct from ArticlePolicy (the platform-wide wiki),
 * whose all()/view() return true for everyone.
 */
final class OrganizationArticlePolicyAbstractTest extends TestCase
{
    public function test_before_returns_true_for_super_admin(): void
    {
        $policy = new OrganizationArticlePolicy;

        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')->once()->with([Role::SUPER_ADMIN])->andReturn(true);

        $this->assertTrue($policy->before($user));
    }

    public function test_before_defers_for_non_super_admin(): void
    {
        $policy = new OrganizationArticlePolicy;

        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')->once()->with([Role::SUPER_ADMIN])->andReturn(false);

        $this->assertNull($policy->before($user));
    }

    public function test_all_allows_manager_of_organization(): void
    {
        $policy = new OrganizationArticlePolicy;
        $organization = new Organization;
        $organization->id = 3;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization)->andReturn(true);

        $this->assertTrue($policy->all($user, $organization));
    }

    public function test_all_denies_non_manager(): void
    {
        $policy = new OrganizationArticlePolicy;
        $organization = new Organization;
        $organization->id = 3;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization)->andReturn(false);

        $this->assertFalse($policy->all($user, $organization));
    }

    public function test_view_allows_same_org_manager(): void
    {
        $policy = new OrganizationArticlePolicy;
        $organization = new Organization;
        $organization->id = 3;
        $article = Mockery::mock(BelongsToOrganizationContract::class);
        $article->organization_id = 3;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization)->andReturn(true);

        $this->assertTrue($policy->view($user, $organization, $article));
    }

    public function test_view_denies_cross_tenant_article(): void
    {
        $policy = new OrganizationArticlePolicy;
        $organization = new Organization;
        $organization->id = 3;
        $article = Mockery::mock(BelongsToOrganizationContract::class);
        $article->organization_id = 99; // belongs to another org
        $user = Mockery::mock('App\\Models\\User\\User');
        // canManageOrganization must NOT be reached — org mismatch short-circuits.

        $this->assertFalse($policy->view($user, $organization, $article));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
