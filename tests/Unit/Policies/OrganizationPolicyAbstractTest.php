<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use App\Models\Role;
use Mockery;
use Polis\Tests\Fixtures\Models\Organization;
use Polis\Tests\Fixtures\Models\OrganizationManager;
use Polis\Tests\Fixtures\Policies\Organization\OrganizationManagerPolicy;
use Polis\Tests\Fixtures\Policies\Organization\OrganizationPolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for OrganizationPolicyAbstract and OrganizationManagerPolicyAbstract.
 *
 * OrganizationPolicyAbstract:
 *   - all -> always false
 *   - create -> always true
 *   - view / update -> MANAGER on org
 *   - delete -> ADMINISTRATOR on org
 *
 * OrganizationManagerPolicyAbstract:
 *   - all -> MANAGER on org
 *   - create -> ADMINISTRATOR on org
 *   - update / delete -> org-id boundary + ADMINISTRATOR
 */
final class OrganizationPolicyAbstractTest extends TestCase
{
    public function test_org_policy_all_always_returns_false(): void
    {
        $policy = new OrganizationPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertFalse($policy->all($user));
    }

    public function test_org_policy_create_always_returns_true(): void
    {
        $policy = new OrganizationPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertTrue($policy->create($user));
    }

    public function test_org_policy_view_requires_manager(): void
    {
        $policy = new OrganizationPolicy;
        $organization = new Organization;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::MANAGER)->andReturn(true);

        $this->assertTrue($policy->view($user, $organization));
    }

    public function test_org_policy_update_requires_manager(): void
    {
        $policy = new OrganizationPolicy;
        $organization = new Organization;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::MANAGER)->andReturn(false);

        $this->assertFalse($policy->update($user, $organization));
    }

    public function test_org_policy_delete_requires_administrator(): void
    {
        $policy = new OrganizationPolicy;
        $organization = new Organization;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::ADMINISTRATOR)->andReturn(true);

        $this->assertTrue($policy->delete($user, $organization));
    }

    public function test_org_manager_all_requires_manager(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = new Organization;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::MANAGER)->andReturn(true);

        $this->assertTrue($policy->all($user, $organization));
    }

    public function test_org_manager_create_requires_administrator(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = new Organization;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::ADMINISTRATOR)->andReturn(true);

        $this->assertTrue($policy->create($user, $organization));
    }

    public function test_org_manager_update_allows_within_same_org_with_admin(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = new Organization;
        $organization->id = 5;
        $manager = new OrganizationManager;
        $manager->organization_id = 5;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::ADMINISTRATOR)->andReturn(true);

        $this->assertTrue($policy->update($user, $organization, $manager));
    }

    public function test_org_manager_update_denies_cross_org_boundary(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = new Organization;
        $organization->id = 5;
        $manager = new OrganizationManager;
        $manager->organization_id = 99;
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertFalse($policy->update($user, $organization, $manager));
    }

    public function test_org_manager_delete_allows_within_same_org_with_admin(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = new Organization;
        $organization->id = 5;
        $manager = new OrganizationManager;
        $manager->organization_id = 5;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::ADMINISTRATOR)->andReturn(true);

        $this->assertTrue($policy->delete($user, $organization, $manager));
    }

    public function test_org_manager_delete_denies_cross_org_boundary(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = new Organization;
        $organization->id = 5;
        $manager = new OrganizationManager;
        $manager->organization_id = 99;
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertFalse($policy->delete($user, $organization, $manager));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
