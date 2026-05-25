<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Organization;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\User\User;
use App\Policies\Organization\OrganizationManagerPolicy;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class OrganizationManagerPolicyTest
 */
final class OrganizationManagerPolicyTest extends TestCase
{
    use DatabaseSetupTrait;

    public function test_all_blocks_when_not_organization_manager(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($policy->all($user, $organization));
    }

    public function test_all_passes_for_organization_manager(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::MANAGER,
        ]);

        $this->assertTrue($policy->all($user, $organization));
    }

    public function test_create_blocks_when_not_organization_manager(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($policy->create($user, $organization));
    }

    public function test_create_blocks_for_organization_manager(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::MANAGER,
        ]);

        $this->assertFalse($policy->create($user, $organization));
    }

    public function test_create_passes_for_organization_admin(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $this->assertTrue($policy->create($user, $organization));
    }

    public function test_update_blocks_with_organization_mismatch(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organizationManager = OrganizationManager::factory()->create();

        $this->assertFalse($policy->update($user, $organization, $organizationManager));
    }

    public function test_update_blocks_when_not_organization_not(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organizationManager = OrganizationManager::factory()->create([
            'user_id' => $user->id,
            'role_id' => Role::MANAGER,
        ]);

        $this->assertFalse($policy->update($user, $organization, $organizationManager));
    }

    public function test_update_blocks_for_organization_manager(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organizationManager = OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::MANAGER,
        ]);

        $this->assertFalse($policy->update($user, $organization, $organizationManager));
    }

    public function test_update_passes_for_organization_admin(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organizationManager = OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $this->assertTrue($policy->update($user, $organization, $organizationManager));
    }

    public function test_delete_blocks_with_organization_mismatch(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organizationManager = OrganizationManager::factory()->create();

        $this->assertFalse($policy->delete($user, $organization, $organizationManager));
    }

    public function test_delete_blocks_when_not_organization_not(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organizationManager = OrganizationManager::factory()->create([
            'user_id' => $user->id,
            'role_id' => Role::MANAGER,
        ]);

        $this->assertFalse($policy->delete($user, $organization, $organizationManager));
    }

    public function test_delete_blocks_for_organization_manager(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organizationManager = OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::MANAGER,
        ]);

        $this->assertFalse($policy->delete($user, $organization, $organizationManager));
    }

    public function test_delete_passes_for_organization_admin(): void
    {
        $policy = new OrganizationManagerPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organizationManager = OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $this->assertTrue($policy->delete($user, $organization, $organizationManager));
    }
}
