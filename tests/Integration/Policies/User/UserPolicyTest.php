<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\User;

use App\Models\User\User;
use App\Policies\User\UserPolicy;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class UserPolicyTest
 */
final class UserPolicyTest extends TestCase
{
    use DatabaseSetupTrait, RolesTesting;

    public function test_view_self_passes(): void
    {
        $policy = new UserPolicy;

        $loggedInUser = new User;
        $loggedInUser->id = 5;

        $this->assertTrue($policy->viewSelf($loggedInUser));
    }

    public function test_view_success(): void
    {
        $policy = new UserPolicy;

        $this->assertTrue($policy->view(new User, new User));
    }

    public function test_update_success(): void
    {
        $policy = new UserPolicy;

        $loggedInUser = new User;
        $loggedInUser->id = 5;

        $this->assertTrue($policy->update($loggedInUser, $loggedInUser));
    }

    public function test_update_blocks(): void
    {
        $policy = new UserPolicy;

        foreach ($this->rolesWithoutAdmins() as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertFalse($policy->update($user, new User));
        }
    }
}
