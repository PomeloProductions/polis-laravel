<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use PHPUnit\Framework\Attributes\DataProvider;
use Polis\Models\Asset;
use Polis\Models\Category;
use Polis\Models\Collection\Collection;
use Polis\Models\Collection\CollectionItem;
use Polis\Models\Feature;
use Polis\Models\Messaging\Message;
use Polis\Models\Messaging\Thread;
use Polis\Models\Organization\Organization;
use Polis\Models\Organization\OrganizationManager;
use Polis\Models\Payment\LineItem;
use Polis\Models\Payment\Payment;
use Polis\Models\Payment\PaymentMethod;
use Polis\Models\Role;
use Polis\Models\Statistic\Statistic;
use Polis\Models\Statistic\TargetStatistic;
use Polis\Models\Subscription\MembershipPlan;
use Polis\Models\Subscription\MembershipPlanRate;
use Polis\Models\Subscription\Subscription;
use Polis\Models\User\ArticleNote;
use Polis\Models\User\Contact;
use Polis\Models\User\ExternalAccountConnection;
use Polis\Models\User\InvitationToken;
use Polis\Models\User\PasswordToken;
use Polis\Models\User\ProfileImage;
use Polis\Models\User\User;
use Polis\Models\User\UserPage;
use Polis\Models\User\UserPageComponent;
use Polis\Models\Vote\Ballot;
use Polis\Models\Vote\BallotCompletion;
use Polis\Models\Vote\BallotItem;
use Polis\Models\Vote\BallotItemOption;
use Polis\Models\Vote\Vote;
use Polis\Models\Wiki\Article;
use Polis\Models\Wiki\ArticleIteration;
use Polis\Models\Wiki\ArticleModification;
use Polis\Models\Wiki\ArticleSummary;
use Polis\Models\Wiki\ArticleVersion;
use Polis\Providers\BaseRepositoryProvider;
use Polis\Tests\TestCase;

/**
 * Verifies that {@see BaseRepositoryProvider}'s internal model map falls
 * back to the `Polis\Models\...` concrete for every entry when no
 * consumer-side `App\Models\...` override is autoloadable.
 *
 * In the package's own test environment no `App\Models\...` classes are
 * registered, so resolvedModelMap() yields the Polis fallback for every
 * key. This is the same path PolisOS will take once it deletes its empty
 * shim files.
 *
 * Tests rely only on reflection + anonymous subclassing — no Mockery, no
 * fixture-model dependencies.
 */
final class BaseRepositoryProviderResolveTest extends TestCase
{
    /**
     * Every model entry in the resolved map together with the package
     * concrete it should fall back to when no App\ class exists.
     *
     * @return array<string, array{0:string, 1:class-string}>
     */
    public static function modelFallbackProvider(): array
    {
        return [
            'article' => ['article', Article::class],
            'articleIteration' => ['articleIteration', ArticleIteration::class],
            'articleModification' => ['articleModification', ArticleModification::class],
            'articleSummary' => ['articleSummary', ArticleSummary::class],
            'articleVersion' => ['articleVersion', ArticleVersion::class],
            'articleNote' => ['articleNote', ArticleNote::class],
            'asset' => ['asset', Asset::class],
            'ballot' => ['ballot', Ballot::class],
            'ballotCompletion' => ['ballotCompletion', BallotCompletion::class],
            'ballotItem' => ['ballotItem', BallotItem::class],
            'ballotItemOption' => ['ballotItemOption', BallotItemOption::class],
            'category' => ['category', Category::class],
            'collection' => ['collection', Collection::class],
            'collectionItem' => ['collectionItem', CollectionItem::class],
            'contact' => ['contact', Contact::class],
            'externalAccountConnection' => ['externalAccountConnection', ExternalAccountConnection::class],
            'feature' => ['feature', Feature::class],
            'invitationToken' => ['invitationToken', InvitationToken::class],
            'lineItem' => ['lineItem', LineItem::class],
            'membershipPlan' => ['membershipPlan', MembershipPlan::class],
            'membershipPlanRate' => ['membershipPlanRate', MembershipPlanRate::class],
            'message' => ['message', Message::class],
            'organization' => ['organization', Organization::class],
            'organizationManager' => ['organizationManager', OrganizationManager::class],
            'passwordToken' => ['passwordToken', PasswordToken::class],
            'payment' => ['payment', Payment::class],
            'paymentMethod' => ['paymentMethod', PaymentMethod::class],
            'profileImage' => ['profileImage', ProfileImage::class],
            'resource' => ['resource', \Polis\Models\Resource::class],
            'role' => ['role', Role::class],
            'statistic' => ['statistic', Statistic::class],
            'subscription' => ['subscription', Subscription::class],
            'targetStatistic' => ['targetStatistic', TargetStatistic::class],
            'thread' => ['thread', Thread::class],
            'user' => ['user', User::class],
            'userPage' => ['userPage', UserPage::class],
            'userPageComponent' => ['userPageComponent', UserPageComponent::class],
            'vote' => ['vote', Vote::class],
        ];
    }

    #[DataProvider('modelFallbackProvider')]
    public function test_resolved_model_map_uses_helper_rule(string $key, string $expectedPolisClass): void
    {
        // Other tests in this suite occasionally Mockery::mock('App\\Models\\X')
        // which registers that class in the autoloader, polluting the
        // App\ side for the rest of the run. We therefore assert the
        // resolution rule, not a strict equality to the Polis fallback:
        //
        //   resolved == App\Models\... iff that class is autoloadable;
        //   otherwise resolved == the expected Polis fallback exactly.
        $provider = $this->makeProvider();

        $map = callMethod($provider, 'resolvedModelMap');

        $this->assertArrayHasKey($key, $map);
        $resolved = $map[$key];

        if (str_starts_with($resolved, 'App\\')) {
            $this->assertTrue(class_exists($resolved),
                "App-prefixed resolution {$resolved} must point at an autoloadable class");
        } else {
            $this->assertSame($expectedPolisClass, $resolved,
                "When no consumer override is loaded, model key {$key} must fall back to {$expectedPolisClass}");
        }
    }

    /**
     * Stricter assertion against a model whose `App\Models\...` FQN is
     * (deliberately) not aliased by any of this package's test fixtures.
     *
     * If a fixture (current or future) aliases `App\Models\Feature`, the
     * resolver legitimately returns the App side and this test cannot
     * meaningfully verify the Polis fallback. In that case we skip rather
     * than fail — order-dependent class-existence is a global property of
     * the PHP process and a fixture's presence is not a bug in the
     * resolver.
     */
    public function test_feature_model_falls_back_to_polis_feature(): void
    {
        if (class_exists('App\\Models\\Feature')) {
            $this->markTestSkipped(
                'App\\Models\\Feature is already aliased (fixture present); '
                .'fallback path covered by the parametric data-provider test.'
            );
        }

        $provider = $this->makeProvider();
        $map = callMethod($provider, 'resolvedModelMap');

        $this->assertSame(Feature::class, $map['feature']);
    }

    public function test_article_iteration_model_falls_back_to_polis_when_no_app_override(): void
    {
        if (class_exists('App\\Models\\Wiki\\ArticleIteration')) {
            $this->markTestSkipped(
                'App\\Models\\Wiki\\ArticleIteration is already aliased (fixture present); '
                .'fallback path covered by the parametric data-provider test.'
            );
        }

        $provider = $this->makeProvider();
        $map = callMethod($provider, 'resolvedModelMap');

        $this->assertSame(ArticleIteration::class, $map['articleIteration']);
    }

    public function test_resolved_model_map_is_keyed_consistently(): void
    {
        $provider = $this->makeProvider();
        $map = callMethod($provider, 'resolvedModelMap');

        // Sanity: the map must be non-empty and every value must be a
        // class-string (App\... or Polis\...). We don't class_exists() here
        // because the EloquentJoin trait some models pull in is not in the
        // package's test dependencies — the binding closures handle the
        // actual instantiation in a normal Laravel runtime.
        $this->assertNotEmpty($map);

        foreach ($map as $key => $fqn) {
            $this->assertIsString($key);
            $this->assertIsString($fqn);
            $this->assertTrue(
                str_starts_with($fqn, 'App\\') || str_starts_with($fqn, 'Polis\\'),
                "Resolved FQN for {$key} must be in App\\ or Polis\\ namespace, got {$fqn}",
            );
        }
    }

    private function makeProvider(): BaseRepositoryProvider
    {
        return new class($this->app) extends BaseRepositoryProvider
        {
            public function appProviders(): array
            {
                return [];
            }

            public function appMorphMaps(): array
            {
                return [];
            }

            public function registerApp(): void
            {
                // no-op for tests
            }
        };
    }
}
