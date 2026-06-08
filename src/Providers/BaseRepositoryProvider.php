<?php

declare(strict_types=1);

namespace Polis\Providers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use App\Models\Feature;
use App\Models\Messaging\Message;
use App\Models\Messaging\Thread;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Payment\LineItem;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentMethod;
use App\Models\Role;
use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use App\Models\User\ArticleNote;
use App\Models\User\Contact;
use App\Models\User\ExternalAccountConnection;
use App\Models\User\InvitationToken;
use App\Models\User\PasswordToken;
use App\Models\User\ProfileImage;
use App\Models\User\User;
use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use App\Models\Vote\Ballot;
use App\Models\Vote\BallotCompletion;
use App\Models\Vote\BallotItem;
use App\Models\Vote\BallotItemOption;
use App\Models\Vote\Vote;
use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use App\Models\Wiki\ArticleModification;
use App\Models\Wiki\ArticleSummary;
use App\Models\Wiki\ArticleVersion;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\ServiceProvider;
use Polis\Contracts\Repositories\AssetRepositoryContract;
use Polis\Contracts\Repositories\CategoryRepositoryContract;
use Polis\Contracts\Repositories\Collection\CollectionItemRepositoryContract;
use Polis\Contracts\Repositories\Collection\CollectionRepositoryContract;
use Polis\Contracts\Repositories\FeatureRepositoryContract;
use Polis\Contracts\Repositories\Messaging\EmailTemplateRepositoryContract;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Contracts\Repositories\Messaging\PushTemplateRepositoryContract;
use Polis\Contracts\Repositories\Messaging\ThreadRepositoryContract;
use Polis\Contracts\Repositories\Organization\OrganizationManagerRepositoryContract;
use Polis\Contracts\Repositories\Organization\OrganizationRepositoryContract;
use Polis\Contracts\Repositories\Payment\LineItemRepositoryContract;
use Polis\Contracts\Repositories\Payment\PaymentMethodRepositoryContract;
use Polis\Contracts\Repositories\Payment\PaymentRepositoryContract;
use Polis\Contracts\Repositories\ResourceRepositoryContract;
use Polis\Contracts\Repositories\RoleRepositoryContract;
use Polis\Contracts\Repositories\Statistic\StatisticRepositoryContract;
use Polis\Contracts\Repositories\Statistic\TargetStatisticRepositoryContract;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRateRepositoryContract;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRepositoryContract;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Contracts\Repositories\User\ArticleNoteRepositoryContract;
use Polis\Contracts\Repositories\User\ContactRepositoryContract;
use Polis\Contracts\Repositories\User\ExternalAccountConnectionRepositoryContract;
use Polis\Contracts\Repositories\User\InvitationTokenRepositoryContract;
use Polis\Contracts\Repositories\User\PasswordTokenRepositoryContract;
use Polis\Contracts\Repositories\User\ProfileImageRepositoryContract;
use Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract;
use Polis\Contracts\Repositories\User\UserPageRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Contracts\Repositories\Vote\BallotCompletionRepositoryContract;
use Polis\Contracts\Repositories\Vote\BallotItemOptionRepositoryContract;
use Polis\Contracts\Repositories\Vote\BallotItemRepositoryContract;
use Polis\Contracts\Repositories\Vote\BallotRepositoryContract;
use Polis\Contracts\Repositories\Vote\VoteRepositoryContract;
use Polis\Contracts\Repositories\Wiki\ArticleIterationRepositoryContract;
use Polis\Contracts\Repositories\Wiki\ArticleModificationRepositoryContract;
use Polis\Contracts\Repositories\Wiki\ArticleRepositoryContract;
use Polis\Contracts\Repositories\Wiki\ArticleSummaryRepositoryContract;
use Polis\Contracts\Repositories\Wiki\ArticleVersionRepositoryContract;
use Polis\Contracts\Services\Asset\AssetConfigurationServiceContract;
use Polis\Contracts\Services\TokenGenerationServiceContract;
use Polis\Models\Messaging\EmailTemplate;
use Polis\Models\Messaging\PushTemplate;
use Polis\Repositories\AssetRepository;
use Polis\Repositories\CategoryRepository;
use Polis\Repositories\Collection\CollectionItemRepository;
use Polis\Repositories\Collection\CollectionRepository;
use Polis\Repositories\FeatureRepository;
use Polis\Repositories\Messaging\EmailTemplateRepository;
use Polis\Repositories\Messaging\MessageRepository;
use Polis\Repositories\Messaging\PushTemplateRepository;
use Polis\Repositories\Messaging\ThreadRepository;
use Polis\Repositories\Organization\OrganizationManagerRepository;
use Polis\Repositories\Organization\OrganizationRepository;
use Polis\Repositories\Payment\LineItemRepository;
use Polis\Repositories\Payment\PaymentMethodRepository;
use Polis\Repositories\Payment\PaymentRepository;
use Polis\Repositories\ResourceRepository;
use Polis\Repositories\RoleRepository;
use Polis\Repositories\Statistic\StatisticFilterRepository;
use Polis\Repositories\Statistic\StatisticRepository;
use Polis\Repositories\Statistic\TargetStatisticRepository;
use Polis\Repositories\Subscription\MembershipPlanRateRepository;
use Polis\Repositories\Subscription\MembershipPlanRepository;
use Polis\Repositories\Subscription\SubscriptionRepository;
use Polis\Repositories\User\ArticleNoteRepository;
use Polis\Repositories\User\ContactRepository;
use Polis\Repositories\User\ExternalAccountConnectionRepository;
use Polis\Repositories\User\InvitationTokenRepository;
use Polis\Repositories\User\PasswordTokenRepository;
use Polis\Repositories\User\ProfileImageRepository;
use Polis\Repositories\User\UserPageComponentRepository;
use Polis\Repositories\User\UserPageRepository;
use Polis\Repositories\User\UserRepository;
use Polis\Repositories\Vote\BallotCompletionRepository;
use Polis\Repositories\Vote\BallotItemOptionRepository;
use Polis\Repositories\Vote\BallotItemRepository;
use Polis\Repositories\Vote\BallotRepository;
use Polis\Repositories\Vote\VoteRepository;
use Polis\Repositories\Wiki\ArticleIterationRepository;
use Polis\Repositories\Wiki\ArticleModificationRepository;
use Polis\Repositories\Wiki\ArticleRepository;
use Polis\Repositories\Wiki\ArticleSummaryRepository;
use Polis\Repositories\Wiki\ArticleVersionRepository;

/**
 * Base repository provider for polis-laravel.
 *
 * Auto-bind behaviour
 * -------------------
 * Every model instantiation and morph-map entry resolves via
 * {@see BaseServiceProvider::resolveConsumerOrPackage()}: if the consumer
 * application has supplied a concrete `App\Models\...` subclass, that
 * subclass is used; otherwise the `Polis\Models\...` concrete shipped with
 * this package is used.
 *
 * As a result, consumers no longer need to maintain empty shim classes at
 * `App\Models\...` simply to satisfy provider bindings. All repository
 * concretes (`Polis\Repositories\...`) are non-abstract so no shimming is
 * required at the repository layer either.
 *
 * Required consumer-side artifacts
 * --------------------------------
 * None. Every binding in this provider works against package concretes
 * out of the box and only switches when a consumer subclass is detected.
 */
abstract class BaseRepositoryProvider extends ServiceProvider
{
    /**
     * @return array Holds information on every contract that is provided with this provider
     */
    final public function provides(): array
    {
        return array_merge([
            ArticleRepositoryContract::class,
            ArticleIterationRepositoryContract::class,
            ArticleModificationRepositoryContract::class,
            ArticleSummaryRepositoryContract::class,
            ArticleVersionRepositoryContract::class,
            ArticleNoteRepositoryContract::class,
            AssetRepositoryContract::class,
            BallotRepositoryContract::class,
            BallotCompletionRepositoryContract::class,
            BallotItemRepositoryContract::class,
            BallotItemOptionRepositoryContract::class,
            CategoryRepositoryContract::class,
            CollectionRepositoryContract::class,
            CollectionItemRepositoryContract::class,
            ContactRepositoryContract::class,
            EmailTemplateRepositoryContract::class,
            ExternalAccountConnectionRepositoryContract::class,
            FeatureRepositoryContract::class,
            InvitationTokenRepositoryContract::class,
            LineItemRepositoryContract::class,
            MembershipPlanRepositoryContract::class,
            MembershipPlanRateRepositoryContract::class,
            MessageRepositoryContract::class,
            OrganizationRepositoryContract::class,
            OrganizationManagerRepositoryContract::class,
            PasswordTokenRepositoryContract::class,
            PaymentRepositoryContract::class,
            PaymentMethodRepositoryContract::class,
            ProfileImageRepositoryContract::class,
            PushTemplateRepositoryContract::class,
            ResourceRepositoryContract::class,
            RoleRepositoryContract::class,
            StatisticRepositoryContract::class,
            SubscriptionRepositoryContract::class,
            TargetStatisticRepositoryContract::class,
            ThreadRepositoryContract::class,
            UserPageRepositoryContract::class,
            UserPageComponentRepositoryContract::class,
            UserRepositoryContract::class,
            VoteRepositoryContract::class,
        ], $this->appProviders());
    }

    /**
     * All app specific repositories that are provided here
     */
    abstract public function appProviders(): array;

    /**
     * Returns the FQN map of every model used by this provider, with each
     * value resolved to the consumer's `App\Models\...` override if one
     * exists, otherwise falling back to the package `Polis\Models\...`
     * concrete.
     *
     * Centralising the mapping here keeps the binding closures readable and
     * means new models only need to be added in one place.
     *
     * @return array<string, class-string>
     */
    protected function resolvedModelMap(): array
    {
        return [
            'article' => BaseServiceProvider::resolveConsumerOrPackage(
                Article::class,
                \Polis\Models\Wiki\Article::class,
            ),
            'articleIteration' => BaseServiceProvider::resolveConsumerOrPackage(
                ArticleIteration::class,
                \Polis\Models\Wiki\ArticleIteration::class,
            ),
            'articleModification' => BaseServiceProvider::resolveConsumerOrPackage(
                ArticleModification::class,
                \Polis\Models\Wiki\ArticleModification::class,
            ),
            'articleSummary' => BaseServiceProvider::resolveConsumerOrPackage(
                ArticleSummary::class,
                \Polis\Models\Wiki\ArticleSummary::class,
            ),
            'articleVersion' => BaseServiceProvider::resolveConsumerOrPackage(
                ArticleVersion::class,
                \Polis\Models\Wiki\ArticleVersion::class,
            ),
            'articleNote' => BaseServiceProvider::resolveConsumerOrPackage(
                ArticleNote::class,
                \Polis\Models\User\ArticleNote::class,
            ),
            'asset' => BaseServiceProvider::resolveConsumerOrPackage(
                Asset::class,
                \Polis\Models\Asset::class,
            ),
            'ballot' => BaseServiceProvider::resolveConsumerOrPackage(
                Ballot::class,
                \Polis\Models\Vote\Ballot::class,
            ),
            'ballotCompletion' => BaseServiceProvider::resolveConsumerOrPackage(
                BallotCompletion::class,
                \Polis\Models\Vote\BallotCompletion::class,
            ),
            'ballotItem' => BaseServiceProvider::resolveConsumerOrPackage(
                BallotItem::class,
                \Polis\Models\Vote\BallotItem::class,
            ),
            'ballotItemOption' => BaseServiceProvider::resolveConsumerOrPackage(
                BallotItemOption::class,
                \Polis\Models\Vote\BallotItemOption::class,
            ),
            'category' => BaseServiceProvider::resolveConsumerOrPackage(
                Category::class,
                \Polis\Models\Category::class,
            ),
            'collection' => BaseServiceProvider::resolveConsumerOrPackage(
                Collection::class,
                \Polis\Models\Collection\Collection::class,
            ),
            'collectionItem' => BaseServiceProvider::resolveConsumerOrPackage(
                CollectionItem::class,
                \Polis\Models\Collection\CollectionItem::class,
            ),
            'contact' => BaseServiceProvider::resolveConsumerOrPackage(
                Contact::class,
                \Polis\Models\User\Contact::class,
            ),
            'externalAccountConnection' => BaseServiceProvider::resolveConsumerOrPackage(
                ExternalAccountConnection::class,
                \Polis\Models\User\ExternalAccountConnection::class,
            ),
            'feature' => BaseServiceProvider::resolveConsumerOrPackage(
                Feature::class,
                \Polis\Models\Feature::class,
            ),
            'invitationToken' => BaseServiceProvider::resolveConsumerOrPackage(
                InvitationToken::class,
                \Polis\Models\User\InvitationToken::class,
            ),
            'lineItem' => BaseServiceProvider::resolveConsumerOrPackage(
                LineItem::class,
                \Polis\Models\Payment\LineItem::class,
            ),
            'membershipPlan' => BaseServiceProvider::resolveConsumerOrPackage(
                MembershipPlan::class,
                \Polis\Models\Subscription\MembershipPlan::class,
            ),
            'membershipPlanRate' => BaseServiceProvider::resolveConsumerOrPackage(
                MembershipPlanRate::class,
                \Polis\Models\Subscription\MembershipPlanRate::class,
            ),
            'message' => BaseServiceProvider::resolveConsumerOrPackage(
                Message::class,
                \Polis\Models\Messaging\Message::class,
            ),
            'organization' => BaseServiceProvider::resolveConsumerOrPackage(
                Organization::class,
                \Polis\Models\Organization\Organization::class,
            ),
            'organizationManager' => BaseServiceProvider::resolveConsumerOrPackage(
                OrganizationManager::class,
                \Polis\Models\Organization\OrganizationManager::class,
            ),
            'passwordToken' => BaseServiceProvider::resolveConsumerOrPackage(
                PasswordToken::class,
                \Polis\Models\User\PasswordToken::class,
            ),
            'payment' => BaseServiceProvider::resolveConsumerOrPackage(
                Payment::class,
                \Polis\Models\Payment\Payment::class,
            ),
            'paymentMethod' => BaseServiceProvider::resolveConsumerOrPackage(
                PaymentMethod::class,
                \Polis\Models\Payment\PaymentMethod::class,
            ),
            'profileImage' => BaseServiceProvider::resolveConsumerOrPackage(
                ProfileImage::class,
                \Polis\Models\User\ProfileImage::class,
            ),
            'resource' => BaseServiceProvider::resolveConsumerOrPackage(
                \App\Models\Resource::class,
                \Polis\Models\Resource::class,
            ),
            'role' => BaseServiceProvider::resolveConsumerOrPackage(
                Role::class,
                \Polis\Models\Role::class,
            ),
            'statistic' => BaseServiceProvider::resolveConsumerOrPackage(
                Statistic::class,
                \Polis\Models\Statistic\Statistic::class,
            ),
            'subscription' => BaseServiceProvider::resolveConsumerOrPackage(
                Subscription::class,
                \Polis\Models\Subscription\Subscription::class,
            ),
            'targetStatistic' => BaseServiceProvider::resolveConsumerOrPackage(
                TargetStatistic::class,
                \Polis\Models\Statistic\TargetStatistic::class,
            ),
            'thread' => BaseServiceProvider::resolveConsumerOrPackage(
                Thread::class,
                \Polis\Models\Messaging\Thread::class,
            ),
            'user' => BaseServiceProvider::resolveConsumerOrPackage(
                User::class,
                \Polis\Models\User\User::class,
            ),
            'userPage' => BaseServiceProvider::resolveConsumerOrPackage(
                UserPage::class,
                \Polis\Models\User\UserPage::class,
            ),
            'userPageComponent' => BaseServiceProvider::resolveConsumerOrPackage(
                UserPageComponent::class,
                \Polis\Models\User\UserPageComponent::class,
            ),
            'vote' => BaseServiceProvider::resolveConsumerOrPackage(
                Vote::class,
                \Polis\Models\Vote\Vote::class,
            ),
        ];
    }

    /**
     * Register the repositories.
     */
    final public function register(): void
    {
        $models = $this->resolvedModelMap();

        Relation::morphMap(array_merge([
            'article' => $models['article'],
            'organization' => $models['organization'],
            'subscription' => $models['subscription'],
            'user' => $models['user'],
            'collection' => $models['collection'],
        ], $this->appMorphMaps()));

        $this->app->bind(ArticleRepositoryContract::class, function () use ($models) {
            return new ArticleRepository(
                new $models['article'],
                $this->app->make('log'),
                $this->app->make(StatisticRepositoryContract::class),
            );
        });
        $this->app->bind(ArticleIterationRepositoryContract::class, function () use ($models) {
            return new ArticleIterationRepository(
                new $models['articleIteration'],
                $this->app->make('log'),
            );
        });
        $this->app->bind(ArticleModificationRepositoryContract::class, function () use ($models) {
            return new ArticleModificationRepository(
                new $models['articleModification'],
                $this->app->make('log'),
            );
        });
        $this->app->bind(ArticleSummaryRepositoryContract::class, function () use ($models) {
            return new ArticleSummaryRepository(
                new $models['articleSummary'],
                $this->app->make('log'),
            );
        });
        $this->app->bind(ArticleVersionRepositoryContract::class, function () use ($models) {
            return new ArticleVersionRepository(
                new $models['articleVersion'],
                $this->app->make('log'),
                $this->app->make(Dispatcher::class),
            );
        });
        $this->app->bind(ArticleNoteRepositoryContract::class, function () use ($models) {
            return new ArticleNoteRepository(
                new $models['articleNote'],
                $this->app->make('log'),
                $this->app->make(Dispatcher::class),
            );
        });
        $this->app->bind(AssetRepositoryContract::class, function () use ($models) {
            return new AssetRepository(
                new $models['asset'],
                $this->app->make('log'),
                $this->app->make('filesystem'),
                $this->app->make(AssetConfigurationServiceContract::class)
            );
        });
        $this->app->bind(BallotRepositoryContract::class, function () use ($models) {
            return new BallotRepository(
                new $models['ballot'],
                $this->app->make('log'),
                $this->app->make(BallotItemRepositoryContract::class)
            );
        });
        $this->app->bind(BallotCompletionRepositoryContract::class, function () use ($models) {
            return new BallotCompletionRepository(
                new $models['ballotCompletion'],
                $this->app->make('log'),
                $this->app->make(VoteRepositoryContract::class)
            );
        });
        $this->app->bind(BallotItemRepositoryContract::class, function () use ($models) {
            return new BallotItemRepository(
                new $models['ballotItem'],
                $this->app->make('log'),
                $this->app->make(BallotItemOptionRepositoryContract::class)
            );
        });
        $this->app->bind(BallotItemOptionRepositoryContract::class, function () use ($models) {
            return new BallotItemOptionRepository(
                new $models['ballotItemOption'],
                $this->app->make('log')
            );
        });
        $this->app->bind(CategoryRepositoryContract::class, function () use ($models) {
            return new CategoryRepository(
                new $models['category'],
                $this->app->make('log')
            );
        });
        $this->app->bind(CollectionRepositoryContract::class, function () use ($models) {
            return new CollectionRepository(
                new $models['collection'],
                $this->app->make('log'),
                $this->app->make(CollectionItemRepositoryContract::class)
            );
        });
        $this->app->bind(CollectionItemRepositoryContract::class, function () use ($models) {
            return new CollectionItemRepository(
                new $models['collectionItem'],
                $this->app->make('log')
            );
        });
        $this->app->bind(ContactRepositoryContract::class, function () use ($models) {
            return new ContactRepository(
                new $models['contact'],
                $this->app->make('log')
            );
        });
        $this->app->bind(ExternalAccountConnectionRepositoryContract::class, function () use ($models) {
            return new ExternalAccountConnectionRepository(
                new $models['externalAccountConnection'],
                $this->app->make('log')
            );
        });
        $this->app->bind(EmailTemplateRepositoryContract::class, function () {
            // EmailTemplate is a package-only model; there is no consumer
            // override slot for it.
            return new EmailTemplateRepository(
                new EmailTemplate,
                $this->app->make('log'),
            );
        });
        $this->app->bind(FeatureRepositoryContract::class, function () use ($models) {
            return new FeatureRepository(
                new $models['feature'],
                $this->app->make('log')
            );
        });
        $this->app->bind(LineItemRepositoryContract::class, function () use ($models) {
            return new LineItemRepository(
                new $models['lineItem'],
                $this->app->make('log')
            );
        });
        $this->app->bind(MembershipPlanRepositoryContract::class, function () use ($models) {
            return new MembershipPlanRepository(
                new $models['membershipPlan'],
                $this->app->make('log'),
                $this->app->make(MembershipPlanRateRepositoryContract::class)
            );
        });
        $this->app->bind(MembershipPlanRateRepositoryContract::class, function () use ($models) {
            return new MembershipPlanRateRepository(
                new $models['membershipPlanRate'],
                $this->app->make('log')
            );
        });
        $this->app->bind(MessageRepositoryContract::class, function () use ($models) {
            return new MessageRepository(
                new $models['message'],
                $this->app->make('log'),
                $this->app->make(UserRepositoryContract::class)
            );
        });
        $this->app->bind(OrganizationRepositoryContract::class, function () use ($models) {
            return new OrganizationRepository(
                new $models['organization'],
                $this->app->make('log')
            );
        });
        $this->app->bind(OrganizationManagerRepositoryContract::class, function () use ($models) {
            return new OrganizationManagerRepository(
                new $models['organizationManager'],
                $this->app->make('log')
            );
        });
        $this->app->bind(InvitationTokenRepositoryContract::class, function () use ($models) {
            return new InvitationTokenRepository(
                new $models['invitationToken'],
                $this->app->make('log'),
                $this->app->make(TokenGenerationServiceContract::class)
            );
        });
        $this->app->bind(PasswordTokenRepositoryContract::class, function () use ($models) {
            return new PasswordTokenRepository(
                new $models['passwordToken'],
                $this->app->make('log'),
                $this->app->make(Dispatcher::class),
                $this->app->make(TokenGenerationServiceContract::class)
            );
        });
        $this->app->bind(PaymentRepositoryContract::class, function () use ($models) {
            return new PaymentRepository(
                new $models['payment'],
                $this->app->make('log'),
                $this->app->make(LineItemRepositoryContract::class)
            );
        });
        $this->app->bind(PaymentMethodRepositoryContract::class, function () use ($models) {
            return new PaymentMethodRepository(
                new $models['paymentMethod'],
                $this->app->make('log')
            );
        });
        $this->app->bind(ProfileImageRepositoryContract::class, function () use ($models) {
            return new ProfileImageRepository(
                new $models['profileImage'],
                $this->app->make('log'),
                $this->app->make(FilesystemManager::class),
                $this->app->make(AssetConfigurationServiceContract::class)
            );
        });
        $this->app->bind(PushTemplateRepositoryContract::class, function () {
            // PushTemplate is a package-only model; there is no consumer
            // override slot for it.
            return new PushTemplateRepository(
                new PushTemplate,
                $this->app->make('log'),
            );
        });
        $this->app->bind(ResourceRepositoryContract::class, function () use ($models) {
            return new ResourceRepository(
                new $models['resource'],
                $this->app->make('log')
            );
        });
        $this->app->bind(RoleRepositoryContract::class, function () use ($models) {
            return new RoleRepository(
                new $models['role'],
                $this->app->make('log')
            );
        });
        $this->app->bind(StatisticRepositoryContract::class, function () use ($models) {
            return new StatisticRepository(
                new $models['statistic'],
                $this->app->make('log'),
                $this->app->make(StatisticFilterRepository::class),
                $this->app->make(Dispatcher::class)
            );
        });
        $this->app->bind(SubscriptionRepositoryContract::class, function () use ($models) {
            return new SubscriptionRepository(
                new $models['subscription'],
                $this->app->make('log'),
                $this->app->make(MembershipPlanRateRepositoryContract::class)
            );
        });
        $this->app->bind(TargetStatisticRepositoryContract::class, function () use ($models) {
            return new TargetStatisticRepository(
                new $models['targetStatistic'],
                $this->app->make('log')
            );
        });
        $this->app->bind(ThreadRepositoryContract::class, function () use ($models) {
            return new ThreadRepository(
                new $models['thread'],
                $this->app->make('log')
            );
        });
        $this->app->bind(UserPageRepositoryContract::class, function () use ($models) {
            return new UserPageRepository(
                new $models['userPage'],
                $this->app->make('log')
            );
        });
        $this->app->bind(UserPageComponentRepositoryContract::class, function () use ($models) {
            return new UserPageComponentRepository(
                new $models['userPageComponent'],
                $this->app->make('log')
            );
        });
        $this->app->bind(UserRepositoryContract::class, function () use ($models) {
            return new UserRepository(
                new $models['user'],
                $this->app->make('log'),
                $this->app->make(Hasher::class),
                $this->app->make(Repository::class)
            );
        });
        $this->app->bind(VoteRepositoryContract::class, function () use ($models) {
            return new VoteRepository(
                new $models['vote'],
                $this->app->make('log')
            );
        });
        $this->registerApp();
    }

    /**
     * Gets all morph maps application specific
     */
    abstract public function appMorphMaps(): array;

    /**
     * Runs any app specific registrations
     */
    abstract public function registerApp(): void;
}
