<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use PHPUnit\Framework\Attributes\DataProvider;
use Polis\Models\Category;
use Polis\Models\Collection\Collection as PolisCollection;
use Polis\Models\Collection\CollectionItem;
use Polis\Models\Feature;
use Polis\Models\Organization\Organization;
use Polis\Models\Organization\OrganizationManager;
use Polis\Models\Payment\PaymentMethod;
use Polis\Models\Role;
use Polis\Models\Statistic\Statistic;
use Polis\Models\Subscription\MembershipPlan;
use Polis\Models\Subscription\Subscription;
use Polis\Models\User\User;
use Polis\Models\Vote\Ballot;
use Polis\Models\Vote\BallotCompletion;
use Polis\Models\Wiki\Article;
use Polis\Models\Wiki\ArticleIteration;
use Polis\Providers\BaseRouteServiceProvider;
use Polis\Tests\TestCase;

/**
 * Verifies that {@see BaseRouteServiceProvider::getModelPlaceholders()}
 * falls back to the `Polis\Models\...` concrete when no consumer-side
 * `App\Models\...` override is defined.
 *
 * Inside the package's own CI environment there are no `App\Models\...`
 * classes on the autoloader, so the helper deterministically yields the
 * Polis fallback for every placeholder. This is exactly the path consumer
 * applications take when they drop their previously-required empty shims.
 *
 * Tests are Mockery-only (anonymous subclass for the abstract provider) so
 * they don't depend on the parallel fixture-models branch.
 */
final class BaseRouteServiceProviderResolveTest extends TestCase
{
    /**
     * The full placeholder -> Polis concrete fallback map this provider is
     * expected to resolve to in the absence of any consumer overrides.
     *
     * @return array<string, array{0:string, 1:class-string}>
     */
    public static function placeholderFallbackProvider(): array
    {
        return [
            'article' => ['article', Article::class],
            'article_iteration' => ['article_iteration', ArticleIteration::class],
            'ballot' => ['ballot', Ballot::class],
            'ballot_completion' => ['ballot_completion', BallotCompletion::class],
            'category' => ['category', Category::class],
            'collection' => ['collection', PolisCollection::class],
            'collection_item' => ['collection_item', CollectionItem::class],
            'feature' => ['feature', Feature::class],
            'membership_plan' => ['membership_plan', MembershipPlan::class],
            'organization' => ['organization', Organization::class],
            'organization_manager' => ['organization_manager', OrganizationManager::class],
            'payment_method' => ['payment_method', PaymentMethod::class],
            'role' => ['role', Role::class],
            'statistic' => ['statistic', Statistic::class],
            'subscription' => ['subscription', Subscription::class],
            'user' => ['user', User::class],
        ];
    }

    #[DataProvider('placeholderFallbackProvider')]
    public function test_placeholder_resolves_to_polis_or_app_consistently(string $placeholder, string $expectedPolisClass): void
    {
        // The placeholder must resolve to *either* the Polis fallback or an
        // `App\Models\...` consumer override. Other tests in this suite
        // occasionally Mockery::mock('App\\Models\\X') which registers that
        // class in the autoloader as a side effect — so the package's own
        // CI run can't always rely on the App\ side being empty for every
        // model. The contract we assert is therefore the resolution *rule*:
        //
        //   resolved == App\Models\... iff that App class is autoloadable;
        //   otherwise resolved == the expected Polis fallback exactly.
        $provider = $this->makeProvider();

        $placeholders = $provider->getModelPlaceholders();

        $this->assertArrayHasKey($placeholder, $placeholders);
        $resolved = $placeholders[$placeholder];

        if (str_starts_with($resolved, 'App\\')) {
            $this->assertTrue(class_exists($resolved),
                "App-prefixed resolution {$resolved} must point at an autoloadable class");
        } else {
            $this->assertSame($expectedPolisClass, $resolved,
                "When no consumer override is loaded, placeholder {$placeholder} must fall back to {$expectedPolisClass}");
        }
    }

    /**
     * Stricter assertion isolated to a placeholder whose `App\Models\...`
     * FQN is (deliberately) not aliased by any of this package's test
     * fixtures.
     *
     * If a fixture (current or future) aliases `App\Models\Feature`, the
     * resolver legitimately returns the App side and this test cannot
     * meaningfully verify the Polis fallback. In that case we skip rather
     * than fail — order-dependent class-existence is a global property of
     * the PHP process and a fixture's presence is not a bug in the
     * resolver.
     */
    public function test_feature_placeholder_falls_back_to_polis_feature(): void
    {
        if (class_exists('App\\Models\\Feature')) {
            $this->markTestSkipped(
                'App\\Models\\Feature is already aliased (fixture present); '
                .'fallback path covered by the parametric data-provider test.'
            );
        }

        $provider = $this->makeProvider();
        $placeholders = $provider->getModelPlaceholders();

        $this->assertSame(Feature::class, $placeholders['feature']);
    }

    /**
     * Same idea for a deeply-namespaced model.
     */
    public function test_article_iteration_placeholder_falls_back_to_polis_when_no_app_override(): void
    {
        if (class_exists('App\\Models\\Wiki\\ArticleIteration')) {
            $this->markTestSkipped(
                'App\\Models\\Wiki\\ArticleIteration is already aliased (fixture present); '
                .'fallback path covered by the parametric data-provider test.'
            );
        }

        $provider = $this->makeProvider();
        $placeholders = $provider->getModelPlaceholders();

        $this->assertSame(ArticleIteration::class, $placeholders['article_iteration']);
    }

    public function test_app_model_placeholders_are_merged_in(): void
    {
        $provider = $this->makeProvider(['my_custom' => 'App\\Models\\Custom']);

        $placeholders = $provider->getModelPlaceholders();

        $this->assertArrayHasKey('my_custom', $placeholders);
        $this->assertSame('App\\Models\\Custom', $placeholders['my_custom']);
    }

    public function test_app_placeholders_override_polis_defaults_for_same_key(): void
    {
        // Demonstrates the array_merge precedence: consumer entries take
        // priority over the package map when keys collide.
        $provider = $this->makeProvider(['user' => 'App\\Models\\CustomUserOverride']);

        $placeholders = $provider->getModelPlaceholders();

        $this->assertSame('App\\Models\\CustomUserOverride', $placeholders['user']);
    }

    /**
     * @param  array<string, string>  $appPlaceholders
     */
    private function makeProvider(array $appPlaceholders = []): BaseRouteServiceProvider
    {
        return new class($this->app, $appPlaceholders) extends BaseRouteServiceProvider
        {
            public function __construct($app, private readonly array $appPlaceholders = [])
            {
                parent::__construct($app);
            }

            public function getAppModelPlaceholders(): array
            {
                return $this->appPlaceholders;
            }
        };
    }
}
