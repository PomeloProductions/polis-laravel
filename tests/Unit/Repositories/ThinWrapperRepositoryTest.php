<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories;

use Mockery;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Repositories\CategoryRepository;
use Polis\Repositories\Collection\CollectionItemRepository;
use Polis\Repositories\FeatureRepository;
use Polis\Repositories\Organization\OrganizationManagerRepository;
use Polis\Repositories\Organization\OrganizationRepository;
use Polis\Repositories\Payment\LineItemRepository;
use Polis\Repositories\Payment\PaymentMethodRepository;
use Polis\Repositories\ResourceRepository;
use Polis\Repositories\Statistic\StatisticFilterRepository;
use Polis\Repositories\Subscription\MembershipPlanRateRepository;
use Polis\Repositories\User\UserPageComponentRepository;
use Polis\Repositories\User\UserPageRepository;
use Polis\Repositories\Vote\BallotItemOptionRepository;
use Polis\Repositories\Vote\VoteRepository;
use Polis\Repositories\Wiki\ArticleModificationRepository;
use Polis\Repositories\Wiki\ArticleSummaryRepository;
use Polis\Tests\TestCase;

/**
 * Constructor-coverage smoke tests for the "thin wrapper" repositories —
 * the ones whose only job is to extend BaseRepositoryAbstract and accept a
 * model + logger. There's no specialized behaviour to test, but the
 * constructor itself is uncovered until we instantiate the class, so a
 * single instantiation per class adds it to the coverage report.
 *
 * Each test instantiates the repository and asserts
 * `instanceof BaseRepositoryAbstract` — that's enough to prove the
 * constructor ran without error.
 */
final class ThinWrapperRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // For consumer-app classes that don't already have a fixture
        // stub in tests/Fixtures/Models/, build a minimal
        // BaseModelAbstract subclass under the expected namespace so the
        // repository constructor (which type-hints the consumer-app
        // class) accepts our instances.
        foreach ([
            'App\\Models\\Category',
            'App\\Models\\Resource',
            'App\\Models\\Feature',
            'App\\Models\\Organization\\Organization',
            'App\\Models\\Organization\\OrganizationManager',
            'App\\Models\\Payment\\LineItem',
            'App\\Models\\Statistic\\StatisticFilter',
            'App\\Models\\Collection\\CollectionItem',
            'App\\Models\\Vote\\Vote',
            'App\\Models\\Vote\\BallotItemOption',
            'App\\Models\\Wiki\\ArticleModification',
            'App\\Models\\Wiki\\ArticleSummary',
        ] as $fqcn) {
            if (! class_exists($fqcn, false)) {
                // Build a minimal BaseModelAbstract subclass under the
                // expected namespace so the repository constructors
                // (which type-hint the consumer-app class) accept our
                // instances.
                $parts = explode('\\', $fqcn);
                $short = array_pop($parts);
                $ns = implode('\\', $parts);
                eval("namespace {$ns}; class {$short} extends \\Polis\\Models\\BaseModelAbstract {}");
            }
        }
    }

    public function test_category_repository_instantiates(): void
    {
        $repo = new CategoryRepository(new \App\Models\Category, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_resource_repository_instantiates(): void
    {
        $repo = new ResourceRepository(new \App\Models\Resource, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_feature_repository_instantiates(): void
    {
        $repo = new FeatureRepository(new \App\Models\Feature, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_organization_repository_instantiates(): void
    {
        $repo = new OrganizationRepository(new \App\Models\Organization\Organization, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_organization_manager_repository_instantiates(): void
    {
        $repo = new OrganizationManagerRepository(new \App\Models\Organization\OrganizationManager, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_line_item_repository_instantiates(): void
    {
        $repo = new LineItemRepository(new \App\Models\Payment\LineItem, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_payment_method_repository_instantiates(): void
    {
        $repo = new PaymentMethodRepository(new \App\Models\Payment\PaymentMethod, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_statistic_filter_repository_instantiates(): void
    {
        $repo = new StatisticFilterRepository(new \App\Models\Statistic\StatisticFilter, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_collection_item_repository_instantiates(): void
    {
        $repo = new CollectionItemRepository(new \App\Models\Collection\CollectionItem, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_vote_repository_instantiates(): void
    {
        $repo = new VoteRepository(new \App\Models\Vote\Vote, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_ballot_item_option_repository_instantiates(): void
    {
        $repo = new BallotItemOptionRepository(new \App\Models\Vote\BallotItemOption, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_article_modification_repository_instantiates(): void
    {
        $repo = new ArticleModificationRepository(new \App\Models\Wiki\ArticleModification, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_article_summary_repository_instantiates(): void
    {
        $repo = new ArticleSummaryRepository(new \App\Models\Wiki\ArticleSummary, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_membership_plan_rate_repository_instantiates(): void
    {
        $repo = new MembershipPlanRateRepository(new \App\Models\Subscription\MembershipPlanRate, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_user_page_repository_instantiates(): void
    {
        $repo = new UserPageRepository(new \Polis\Models\User\UserPage, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }

    public function test_user_page_component_repository_instantiates(): void
    {
        $repo = new UserPageComponentRepository(new \Polis\Models\User\UserPageComponent, $this->getGenericLogMock());
        $this->assertInstanceOf(BaseRepositoryAbstract::class, $repo);
    }
}
