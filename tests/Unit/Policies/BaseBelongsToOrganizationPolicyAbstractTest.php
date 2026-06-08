<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use App\Models\Role;
use Mockery;
use Polis\Contracts\Models\BelongsToOrganizationContract;
use Polis\Tests\Fixtures\Models\Organization;
use Polis\Tests\Fixtures\Policies\BaseBelongsToOrganizationAdminPolicy;
use Polis\Tests\Fixtures\Policies\BaseBelongsToOrganizationPolicy;
use Polis\Tests\TestCase;

/**
 * Standalone coverage for BaseBelongsToOrganizationPolicyAbstract.
 *
 * Two concrete subclasses are exercised:
 *   - BaseBelongsToOrganizationPolicy: default ($requiresAdminForManagement = false)
 *     — create/update/delete require MANAGER.
 *   - BaseBelongsToOrganizationAdminPolicy: flipped flag — create/update/delete
 *     require ADMINISTRATOR.
 *
 * The view() gate doesn't read the flag (always MANAGER-equivalent via
 * canManageOrganization with no role arg, which defaults to MANAGER).
 */
final class BaseBelongsToOrganizationPolicyAbstractTest extends TestCase
{
    public function test_all_allows_when_user_can_manage_organization(): void
    {
        $policy = new BaseBelongsToOrganizationPolicy;
        $organization = new Organization;
        $organization->id = 1;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization)->andReturn(true);

        $this->assertTrue($policy->all($user, $organization));
    }

    public function test_all_denies_when_user_cannot_manage_organization(): void
    {
        $policy = new BaseBelongsToOrganizationPolicy;
        $organization = new Organization;
        $organization->id = 1;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization)->andReturn(false);

        $this->assertFalse($policy->all($user, $organization));
    }

    public function test_create_with_manager_default_uses_manager_role(): void
    {
        $policy = new BaseBelongsToOrganizationPolicy;
        $organization = new Organization;
        $organization->id = 1;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::MANAGER)->andReturn(true);

        $this->assertTrue($policy->create($user, $organization));
    }

    public function test_create_with_admin_flag_uses_administrator_role(): void
    {
        $policy = new BaseBelongsToOrganizationAdminPolicy;
        $organization = new Organization;
        $organization->id = 1;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::ADMINISTRATOR)->andReturn(true);

        $this->assertTrue($policy->create($user, $organization));
    }

    public function test_view_allows_when_model_belongs_to_org_and_user_can_manage(): void
    {
        $policy = new BaseBelongsToOrganizationPolicy;
        $organization = new Organization;
        $organization->id = 7;
        $model = Mockery::mock(BelongsToOrganizationContract::class);
        $model->organization_id = 7;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization)->andReturn(true);

        $this->assertTrue($policy->view($user, $organization, $model));
    }

    public function test_view_denies_when_model_belongs_to_different_org(): void
    {
        $policy = new BaseBelongsToOrganizationPolicy;
        $organization = new Organization;
        $organization->id = 7;
        $model = Mockery::mock(BelongsToOrganizationContract::class);
        $model->organization_id = 99; // Different org — boundary violation.
        $user = Mockery::mock('App\\Models\\User\\User');
        // canManageOrganization may or may not be called — depends on short-circuit.
        // The check `$model->organization_id == $organization->id` is first;
        // PHP `&&` short-circuits if it's false, so canManageOrganization should
        // NOT be called. We declare no expectation: Mockery will fail if it IS called.

        $this->assertFalse($policy->view($user, $organization, $model));
    }

    public function test_view_denies_when_user_cannot_manage_org(): void
    {
        $policy = new BaseBelongsToOrganizationPolicy;
        $organization = new Organization;
        $organization->id = 7;
        $model = Mockery::mock(BelongsToOrganizationContract::class);
        $model->organization_id = 7;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization)->andReturn(false);

        $this->assertFalse($policy->view($user, $organization, $model));
    }

    public function test_update_with_manager_default_uses_manager_role(): void
    {
        $policy = new BaseBelongsToOrganizationPolicy;
        $organization = new Organization;
        $organization->id = 7;
        $model = Mockery::mock(BelongsToOrganizationContract::class);
        $model->organization_id = 7;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::MANAGER)->andReturn(true);

        $this->assertTrue($policy->update($user, $organization, $model));
    }

    public function test_update_denies_when_model_belongs_to_different_org(): void
    {
        $policy = new BaseBelongsToOrganizationPolicy;
        $organization = new Organization;
        $organization->id = 7;
        $model = Mockery::mock(BelongsToOrganizationContract::class);
        $model->organization_id = 99;
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertFalse($policy->update($user, $organization, $model));
    }

    public function test_update_with_admin_flag_uses_administrator_role(): void
    {
        $policy = new BaseBelongsToOrganizationAdminPolicy;
        $organization = new Organization;
        $organization->id = 7;
        $model = Mockery::mock(BelongsToOrganizationContract::class);
        $model->organization_id = 7;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::ADMINISTRATOR)->andReturn(true);

        $this->assertTrue($policy->update($user, $organization, $model));
    }

    public function test_delete_with_manager_default_uses_manager_role(): void
    {
        $policy = new BaseBelongsToOrganizationPolicy;
        $organization = new Organization;
        $organization->id = 7;
        $model = Mockery::mock(BelongsToOrganizationContract::class);
        $model->organization_id = 7;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::MANAGER)->andReturn(true);

        $this->assertTrue($policy->delete($user, $organization, $model));
    }

    public function test_delete_with_admin_flag_uses_administrator_role(): void
    {
        $policy = new BaseBelongsToOrganizationAdminPolicy;
        $organization = new Organization;
        $organization->id = 7;
        $model = Mockery::mock(BelongsToOrganizationContract::class);
        $model->organization_id = 7;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('canManageOrganization')->once()->with($organization, Role::ADMINISTRATOR)->andReturn(true);

        $this->assertTrue($policy->delete($user, $organization, $model));
    }

    public function test_delete_denies_when_model_belongs_to_different_org(): void
    {
        $policy = new BaseBelongsToOrganizationPolicy;
        $organization = new Organization;
        $organization->id = 7;
        $model = Mockery::mock(BelongsToOrganizationContract::class);
        $model->organization_id = 99;
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertFalse($policy->delete($user, $organization, $model));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
