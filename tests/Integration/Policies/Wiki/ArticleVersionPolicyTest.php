<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Wiki;

use App\Models\Role;
use App\Models\User\User;
use App\Models\Wiki\Article;
use App\Policies\Wiki\ArticleVersionPolicy;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class ArticleVersionPolicyTest
 */
final class ArticleVersionPolicyTest extends ApplicationTestCase
{
    use RolesTesting;

    public function IterationPolicy()
    {
        $policy = new ArticleVersionPolicy;

        foreach ([Role::ARTICLE_EDITOR, Role::ARTICLE_VIEWER] as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertTrue($policy->all($user));
        }
    }

    public function test_all_blocks(): void
    {
        $policy = new ArticleVersionPolicy;

        foreach ($this->rolesWithoutAdmins([Role::ARTICLE_EDITOR, Role::ARTICLE_VIEWER]) as $role) {
            $user = $this->getUserOfRole($role);

            $this->assertFalse($policy->all($user));
        }
    }

    public function test_create_block(): void
    {
        $policy = new ArticleVersionPolicy;

        $user = User::factory()->create();
        $article = Article::factory()->create();

        $this->assertFalse($policy->create($user, $article));
    }

    public function test_create_passes(): void
    {
        $policy = new ArticleVersionPolicy;

        $user = User::factory()->create();
        $article = Article::factory()->create([
            'created_by_id' => $user->id,
        ]);

        $this->assertTrue($policy->create($user, $article));
    }
}
