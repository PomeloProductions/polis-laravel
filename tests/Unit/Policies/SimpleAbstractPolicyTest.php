<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use App\Models\Role;
use Mockery;
use Polis\Tests\Fixtures\Models\Article;
use Polis\Tests\Fixtures\Models\Ballot;
use Polis\Tests\Fixtures\Models\Category;
use Polis\Tests\Fixtures\Models\Feature;
use Polis\Tests\Fixtures\Policies\CategoryPolicy;
use Polis\Tests\Fixtures\Policies\FeaturePolicy;
use Polis\Tests\Fixtures\Policies\ResourcePolicy;
use Polis\Tests\Fixtures\Policies\RolePolicy;
use Polis\Tests\Fixtures\Policies\Vote\BallotPolicy;
use Polis\Tests\Fixtures\Policies\Wiki\ArticleIterationPolicy;
use Polis\Tests\Fixtures\Policies\Wiki\ArticleVersionPolicy;
use Polis\Tests\TestCase;

/**
 * Bundled coverage for the simplest abstract policies — those whose
 * gate methods are pure constants (true/false) or thin role-checks.
 * Each individual gate is small, but cumulatively they account for a
 * meaningful portion of policy line coverage.
 *
 * Covered:
 *   - ResourcePolicyAbstract  (all)
 *   - CategoryPolicyAbstract  (create / update / delete)
 *   - FeaturePolicyAbstract   (all / view)
 *   - RolePolicyAbstract      (all)
 *   - BallotPolicyAbstract    (view)
 *   - ArticleIterationPolicyAbstract (all)
 *   - ArticleVersionPolicyAbstract (all / create)
 */
final class SimpleAbstractPolicyTest extends TestCase
{
    public function test_resource_policy_all_returns_true_for_any_user(): void
    {
        $policy = new ResourcePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertTrue($policy->all($user));
    }

    public function test_category_policy_create_returns_true(): void
    {
        $policy = new CategoryPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertTrue($policy->create($user));
    }

    public function test_category_policy_update_returns_false(): void
    {
        $policy = new CategoryPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $category = new Category;

        $this->assertFalse($policy->update($user, $category));
    }

    public function test_category_policy_delete_returns_false(): void
    {
        $policy = new CategoryPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $category = new Category;

        $this->assertFalse($policy->delete($user, $category));
    }

    public function test_feature_policy_all_returns_true(): void
    {
        $policy = new FeaturePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertTrue($policy->all($user));
    }

    public function test_feature_policy_view_returns_true(): void
    {
        $policy = new FeaturePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $feature = new Feature;

        $this->assertTrue($policy->view($user, $feature));
    }

    public function test_role_policy_all_returns_false_for_non_super_admin(): void
    {
        $policy = new RolePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertFalse($policy->all($user));
    }

    public function test_ballot_policy_view_returns_true(): void
    {
        $policy = new BallotPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $ballot = new Ballot;

        $this->assertTrue($policy->view($user, $ballot));
    }

    public function test_article_iteration_policy_all_allows_editor(): void
    {
        $policy = new ArticleIterationPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')
            ->once()
            ->with([Role::ARTICLE_EDITOR, Role::ARTICLE_VIEWER])
            ->andReturn(true);

        $this->assertTrue($policy->all($user));
    }

    public function test_article_iteration_policy_all_denies_non_editor(): void
    {
        $policy = new ArticleIterationPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')
            ->once()
            ->with([Role::ARTICLE_EDITOR, Role::ARTICLE_VIEWER])
            ->andReturn(false);

        $this->assertFalse($policy->all($user));
    }

    public function test_article_version_policy_all_allows_viewer(): void
    {
        $policy = new ArticleVersionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')
            ->once()
            ->with([Role::ARTICLE_VIEWER, Role::ARTICLE_EDITOR])
            ->andReturn(true);

        $this->assertTrue($policy->all($user));
    }

    public function test_article_version_policy_create_allows_creator(): void
    {
        $policy = new ArticleVersionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 5;
        $article = new Article;
        $article->created_by_id = 5;

        $this->assertTrue($policy->create($user, $article));
    }

    public function test_article_version_policy_create_denies_non_creator(): void
    {
        $policy = new ArticleVersionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 5;
        $article = new Article;
        $article->created_by_id = 99;

        $this->assertFalse($policy->create($user, $article));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
