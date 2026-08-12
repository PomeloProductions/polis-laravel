<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Wiki;

use App\Models\Role;
use App\Models\User\User;
use App\Models\Wiki\Article;
use App\Policies\Wiki\ArticleSummaryPolicy;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class ArticleSummaryPolicyTest
 */
final class ArticleSummaryPolicyTest extends ApplicationTestCase
{
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
    }

    public function test_view_passes(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $policy = new ArticleSummaryPolicy;

        $this->assertTrue($policy->view($user, $article));
    }

    public function test_create_passes_with_role(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::ARTICLE_EDITOR);
        $article = Article::factory()->create();

        $policy = new ArticleSummaryPolicy;

        $this->assertTrue($policy->create($user, $article));
    }

    public function test_create_fails_without_role(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $policy = new ArticleSummaryPolicy;

        $this->assertFalse($policy->create($user, $article));
    }

    public function test_update_passes_with_role(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::ARTICLE_EDITOR);
        $article = Article::factory()->create();

        $policy = new ArticleSummaryPolicy;

        $this->assertTrue($policy->update($user, $article));
    }

    public function test_update_fails_without_role(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $policy = new ArticleSummaryPolicy;

        $this->assertFalse($policy->update($user, $article));
    }

    public function test_delete_passes_with_role(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::ARTICLE_EDITOR);
        $article = Article::factory()->create();

        $policy = new ArticleSummaryPolicy;

        $this->assertTrue($policy->delete($user, $article));
    }

    public function test_delete_fails_without_role(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $policy = new ArticleSummaryPolicy;

        $this->assertFalse($policy->delete($user, $article));
    }
}
