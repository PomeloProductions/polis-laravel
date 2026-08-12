<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Models\User;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class UserTest
 */
final class UserTest extends ApplicationTestCase
{
    
    public function test_has_role(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->assertFalse($user->hasRole(1));

        $user = User::factory()->create();
        $user->roles()->attach(1);
        $this->assertFalse($user->hasRole(2));

        $user = User::factory()->create();
        $user->roles()->attach(1);
        $this->assertTrue($user->hasRole(1));

        $user = User::factory()->create();
        $user->roles()->attach(1);
        $user->roles()->attach(2);
        $this->assertTrue($user->hasRole(1));

        $user = User::factory()->create();
        $user->roles()->attach(1);
        $user->roles()->attach(2);

        $this->assertTrue($user->hasRole([1, 6]));
        $this->assertTrue($user->hasRole([1, 2]));
        $this->assertTrue($user->hasRole([1]));
        $this->assertTrue($user->hasRole([2]));
        $this->assertTrue($user->hasRole([2, 3]));
        $this->assertFalse($user->hasRole([4, 5]));
    }

    public function test_can_manage_organization(): void
    {
        /** @var Organization $organization */
        $organization = Organization::factory()->create();
        /** @var User $user */
        $user = User::factory()->create();

        $this->assertFalse($user->canManageOrganization($organization));

        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::MANAGER,
        ]);
        $user->refresh();

        $this->assertFalse($user->canManageOrganization($organization, Role::ADMINISTRATOR));
        $this->assertTrue($user->canManageOrganization($organization, Role::MANAGER));

        OrganizationManager::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);
        $user->refresh();
        $this->assertTrue($user->canManageOrganization($organization, Role::ADMINISTRATOR));
    }
}
