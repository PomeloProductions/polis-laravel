<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Wiki;

use App\Models\Role;
use App\Models\Wiki\Article;
use App\Policies\Wiki\ArticlePolicy;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class ArticlePolicyTest
 */
final class ArticlePolicyTest extends ApplicationTestCase
{
    use RolesTesting;

    public function test_all_success(): void
    {
        $policy = new ArticlePolicy;

        foreach ([Role::ARTICLE_EDITOR, Role::ARTICLE_VIEWER] as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertTrue($policy->all($user));
        }
    }

    public function test_view_success(): void
    {
        $policy = new ArticlePolicy;

        foreach ([Role::ARTICLE_EDITOR, Role::ARTICLE_VIEWER] as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertTrue($policy->view($user, new Article));
        }
    }

    public function test_create_success(): void
    {
        $policy = new ArticlePolicy;

        $user = $this->getUserOfRole(Role::ARTICLE_EDITOR);

        $this->assertTrue($policy->create($user));
    }

    public function test_create_blocks(): void
    {
        $policy = new ArticlePolicy;

        foreach ($this->rolesWithoutAdmins([Role::ARTICLE_EDITOR]) as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertFalse($policy->create($user));
        }
    }

    public function test_update_success(): void
    {
        $policy = new ArticlePolicy;

        $user = $this->getUserOfRole(Role::ARTICLE_EDITOR);

        $article = new Article([
            'created_by_id' => $user->id,
        ]);

        $this->assertTrue($policy->update($user, $article));
    }

    public function test_update_blocks(): void
    {
        $policy = new ArticlePolicy;

        foreach ($this->rolesWithoutAdmins([Role::ARTICLE_EDITOR]) as $role) {
            $user = $this->getUserOfRole($role);

            $article = new Article([
                'created_by_id' => $user->id,
            ]);

            $this->assertFalse($policy->update($user, $article));
        }

        $user = $this->getUserOfRole(Role::ARTICLE_EDITOR);

        $article = new Article;
        $this->assertFalse($policy->update($user, $article));
    }
}
