<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Organization;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\User\User;
use App\Policies\Organization\OrganizationPolicy;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class OrganizationPolicyTest
 */
final class OrganizationPolicyTest extends TestCase
{
    use DatabaseSetupTrait;

    public function test_all(): void
    {
        $policy = new OrganizationPolicy;

        $this->assertFalse($policy->all(new User));
    }

    public function test_create(): void
    {
        $policy = new OrganizationPolicy;

        $this->assertTrue($policy->create(new User));
    }

    public function test_view_blocks_when_not_organization_manager(): void
    {
        $policy = new OrganizationPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($policy->view($user, $organization));
    }

    public function test_view_passes_for_organization_manager(): void
    {
        $policy = new OrganizationPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::MANAGER,
        ]);

        $this->assertTrue($policy->view($user, $organization));
    }

    public function test_update_blocks_when_not_organization_manager(): void
    {
        $policy = new OrganizationPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($policy->update($user, $organization));
    }

    public function test_update_passes_for_organization_manager(): void
    {
        $policy = new OrganizationPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::MANAGER,
        ]);

        $this->assertTrue($policy->update($user, $organization));
    }

    public function test_delete_blocks_when_not_organization_manager(): void
    {
        $policy = new OrganizationPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($policy->delete($user, $organization));
    }

    public function test_delete_blocks_for_organization_manager(): void
    {
        $policy = new OrganizationPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::MANAGER,
        ]);

        $this->assertFalse($policy->delete($user, $organization));
    }

    public function test_delete_passes_for_organization_admin(): void
    {
        $policy = new OrganizationPolicy;
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $this->assertTrue($policy->delete($user, $organization));
    }
}
