<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Wiki;

use App\Models\Role;
use App\Policies\Wiki\ArticleIterationPolicy;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class IterationPolicyTest
 */
final class IterationPolicyTest extends TestCase
{
    use DatabaseSetupTrait, RolesTesting;

    public function IterationPolicy()
    {
        $policy = new ArticleIterationPolicy;

        foreach ([Role::ARTICLE_EDITOR, Role::ARTICLE_VIEWER] as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertTrue($policy->all($user));
        }
    }

    public function test_all_blocks(): void
    {
        $policy = new ArticleIterationPolicy;

        foreach ($this->rolesWithoutAdmins([Role::ARTICLE_EDITOR, Role::ARTICLE_VIEWER]) as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertFalse($policy->all($user));
        }
    }
}
