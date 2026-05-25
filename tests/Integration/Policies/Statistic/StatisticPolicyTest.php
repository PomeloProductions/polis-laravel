<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Statistic;

use App\Models\Role;
use App\Models\User\User;
use App\Policies\Statistic\StatisticPolicy;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class StatisticPolicyTest
 */
class StatisticPolicyTest extends TestCase
{
    use DatabaseSetupTrait, RolesTesting;

    public function test_all_passes()
    {
        $policy = new StatisticPolicy;
        $this->assertTrue($policy->all(new User));
    }

    public function test_view_fails_incorrect_role()
    {
        $policy = new StatisticPolicy;

        foreach ($this->rolesWithoutAdmins([Role::CONTENT_EDITOR, Role::SUPPORT_STAFF]) as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertFalse($policy->view($user));
        }
    }

    public function test_view_passes_correct_role()
    {
        $policy = new StatisticPolicy;

        foreach ([Role::CONTENT_EDITOR, Role::SUPPORT_STAFF] as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertTrue($policy->view($user));
        }
    }

    public function test_create_fails_incorrect_role()
    {
        $policy = new StatisticPolicy;

        foreach ($this->rolesWithoutAdmins([Role::CONTENT_EDITOR]) as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertFalse($policy->create($user));
        }
    }

    public function test_create_passes_correct_role()
    {
        $policy = new StatisticPolicy;

        $user = $this->getUserOfRole(Role::CONTENT_EDITOR);

        $this->assertTrue($policy->create($user));
    }

    public function test_update_fails_incorrect_role()
    {
        $policy = new StatisticPolicy;

        foreach ($this->rolesWithoutAdmins([Role::CONTENT_EDITOR]) as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertFalse($policy->update($user));
        }
    }

    public function test_update_passes_correct_role()
    {
        $policy = new StatisticPolicy;

        $user = $this->getUserOfRole(Role::CONTENT_EDITOR);

        $this->assertTrue($policy->update($user));
    }

    public function test_delete_fails_incorrect_role()
    {
        $policy = new StatisticPolicy;

        foreach ($this->rolesWithoutAdmins([Role::CONTENT_EDITOR]) as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertFalse($policy->delete($user));
        }
    }

    public function test_delete_passes_correct_role()
    {
        $policy = new StatisticPolicy;

        $user = $this->getUserOfRole(Role::CONTENT_EDITOR);

        $this->assertTrue($policy->delete($user));
    }
}
