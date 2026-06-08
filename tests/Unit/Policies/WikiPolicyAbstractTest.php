<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use App\Models\Role;
use Mockery;
use Polis\Tests\Fixtures\Models\Article;
use Polis\Tests\Fixtures\Policies\Wiki\ArticlePolicy;
use Polis\Tests\Fixtures\Policies\Wiki\ArticleSummaryPolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for ArticlePolicyAbstract / ArticleSummaryPolicyAbstract.
 *
 * - ArticlePolicy: all/view -> true; create/update -> ARTICLE_EDITOR
 *   (update additionally requires user->id == article->created_by_id).
 * - ArticleSummaryPolicy: view -> true; create/update/delete -> ARTICLE_EDITOR.
 */
final class WikiPolicyAbstractTest extends TestCase
{
    public function test_article_policy_all_returns_true(): void
    {
        $policy = new ArticlePolicy;
        $this->assertTrue($policy->all(Mockery::mock('App\\Models\\User\\User')));
    }

    public function test_article_policy_view_returns_true(): void
    {
        $policy = new ArticlePolicy;
        $article = new Article;
        $this->assertTrue($policy->view(Mockery::mock('App\\Models\\User\\User'), $article));
    }

    public function test_article_policy_create_requires_article_editor(): void
    {
        $policy = new ArticlePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')->once()->with(Role::ARTICLE_EDITOR)->andReturn(true);

        $this->assertTrue($policy->create($user));
    }

    public function test_article_policy_create_denies_non_editor(): void
    {
        $policy = new ArticlePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')->once()->with(Role::ARTICLE_EDITOR)->andReturn(false);

        $this->assertFalse($policy->create($user));
    }

    public function test_article_policy_update_allows_editor_owner(): void
    {
        $policy = new ArticlePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 5;
        $user->shouldReceive('hasRole')->once()->with(Role::ARTICLE_EDITOR)->andReturn(true);
        $article = new Article;
        $article->created_by_id = 5;

        $this->assertTrue($policy->update($user, $article));
    }

    public function test_article_policy_update_denies_editor_non_owner(): void
    {
        $policy = new ArticlePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 5;
        $user->shouldReceive('hasRole')->once()->with(Role::ARTICLE_EDITOR)->andReturn(true);
        $article = new Article;
        $article->created_by_id = 99;

        $this->assertFalse($policy->update($user, $article));
    }

    public function test_article_policy_update_denies_non_editor(): void
    {
        $policy = new ArticlePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 5;
        $user->shouldReceive('hasRole')->once()->with(Role::ARTICLE_EDITOR)->andReturn(false);
        $article = new Article;
        $article->created_by_id = 5;

        $this->assertFalse($policy->update($user, $article));
    }

    public function test_article_summary_view_returns_true(): void
    {
        $policy = new ArticleSummaryPolicy;
        $article = new Article;
        $this->assertTrue($policy->view(Mockery::mock('App\\Models\\User\\User'), $article));
    }

    public function test_article_summary_create_requires_editor(): void
    {
        $policy = new ArticleSummaryPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')->once()->with(Role::ARTICLE_EDITOR)->andReturn(true);
        $article = new Article;

        $this->assertTrue($policy->create($user, $article));
    }

    public function test_article_summary_update_requires_editor(): void
    {
        $policy = new ArticleSummaryPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')->once()->with(Role::ARTICLE_EDITOR)->andReturn(false);
        $article = new Article;

        $this->assertFalse($policy->update($user, $article));
    }

    public function test_article_summary_delete_requires_editor(): void
    {
        $policy = new ArticleSummaryPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')->once()->with(Role::ARTICLE_EDITOR)->andReturn(true);
        $article = new Article;

        $this->assertTrue($policy->delete($user, $article));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
