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
use App\Models\Resource;
use App\Models\Role;
use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use App\Models\User\ArticleNote;
use App\Models\User\Contact;
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
 * Class AtheniaRepositoryProvider
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
     * Register the repositories.
     */
    final public function register(): void
    {
        Relation::morphMap(array_merge([
            'article' => Article::class,
            'organization' => Organization::class,
            'subscription' => Subscription::class,
            'user' => User::class,
            'collection' => Collection::class,
        ], $this->appMorphMaps()));

        $this->app->bind(ArticleRepositoryContract::class, function () {
            return new ArticleRepository(
                new Article,
                $this->app->make('log'),
                $this->app->make(StatisticRepositoryContract::class),
            );
        });
        $this->app->bind(ArticleIterationRepositoryContract::class, function () {
            return new ArticleIterationRepository(
                new ArticleIteration,
                $this->app->make('log'),
            );
        });
        $this->app->bind(ArticleModificationRepositoryContract::class, function () {
            return new ArticleModificationRepository(
                new ArticleModification,
                $this->app->make('log'),
            );
        });
        $this->app->bind(ArticleSummaryRepositoryContract::class, function () {
            return new ArticleSummaryRepository(
                new ArticleSummary,
                $this->app->make('log'),
            );
        });
        $this->app->bind(ArticleVersionRepositoryContract::class, function () {
            return new ArticleVersionRepository(
                new ArticleVersion,
                $this->app->make('log'),
                $this->app->make(Dispatcher::class),
            );
        });
        $this->app->bind(ArticleNoteRepositoryContract::class, function () {
            return new ArticleNoteRepository(
                new ArticleNote,
                $this->app->make('log'),
                $this->app->make(Dispatcher::class),
            );
        });
        $this->app->bind(AssetRepositoryContract::class, function () {
            return new AssetRepository(
                new Asset,
                $this->app->make('log'),
                $this->app->make('filesystem'),
                $this->app->make(AssetConfigurationServiceContract::class)
            );
        });
        $this->app->bind(BallotRepositoryContract::class, function () {
            return new BallotRepository(
                new Ballot,
                $this->app->make('log'),
                $this->app->make(BallotItemRepositoryContract::class)
            );
        });
        $this->app->bind(BallotCompletionRepositoryContract::class, function () {
            return new BallotCompletionRepository(
                new BallotCompletion,
                $this->app->make('log'),
                $this->app->make(VoteRepositoryContract::class)
            );
        });
        $this->app->bind(BallotItemRepositoryContract::class, function () {
            return new BallotItemRepository(
                new BallotItem,
                $this->app->make('log'),
                $this->app->make(BallotItemOptionRepositoryContract::class)
            );
        });
        $this->app->bind(BallotItemOptionRepositoryContract::class, function () {
            return new BallotItemOptionRepository(
                new BallotItemOption,
                $this->app->make('log')
            );
        });
        $this->app->bind(CategoryRepositoryContract::class, function () {
            return new CategoryRepository(
                new Category,
                $this->app->make('log')
            );
        });
        $this->app->bind(CollectionRepositoryContract::class, function () {
            return new CollectionRepository(
                new Collection,
                $this->app->make('log'),
                $this->app->make(CollectionItemRepositoryContract::class)
            );
        });
        $this->app->bind(CollectionItemRepositoryContract::class, function () {
            return new CollectionItemRepository(
                new CollectionItem,
                $this->app->make('log')
            );
        });
        $this->app->bind(ContactRepositoryContract::class, function () {
            return new ContactRepository(
                new Contact,
                $this->app->make('log')
            );
        });
        $this->app->bind(EmailTemplateRepositoryContract::class, function () {
            return new EmailTemplateRepository(
                new EmailTemplate,
                $this->app->make('log'),
            );
        });
        $this->app->bind(FeatureRepositoryContract::class, function () {
            return new FeatureRepository(
                new Feature,
                $this->app->make('log')
            );
        });
        $this->app->bind(LineItemRepositoryContract::class, function () {
            return new LineItemRepository(
                new LineItem,
                $this->app->make('log')
            );
        });
        $this->app->bind(MembershipPlanRepositoryContract::class, function () {
            return new MembershipPlanRepository(
                new MembershipPlan,
                $this->app->make('log'),
                $this->app->make(MembershipPlanRateRepositoryContract::class)
            );
        });
        $this->app->bind(MembershipPlanRateRepositoryContract::class, function () {
            return new MembershipPlanRateRepository(
                new MembershipPlanRate,
                $this->app->make('log')
            );
        });
        $this->app->bind(MessageRepositoryContract::class, function () {
            return new MessageRepository(
                new Message,
                $this->app->make('log'),
                $this->app->make(UserRepositoryContract::class)
            );
        });
        $this->app->bind(OrganizationRepositoryContract::class, function () {
            return new OrganizationRepository(
                new Organization,
                $this->app->make('log')
            );
        });
        $this->app->bind(OrganizationManagerRepositoryContract::class, function () {
            return new OrganizationManagerRepository(
                new OrganizationManager,
                $this->app->make('log')
            );
        });
        $this->app->bind(InvitationTokenRepositoryContract::class, function () {
            return new InvitationTokenRepository(
                new InvitationToken,
                $this->app->make('log'),
                $this->app->make(TokenGenerationServiceContract::class)
            );
        });
        $this->app->bind(PasswordTokenRepositoryContract::class, function () {
            return new PasswordTokenRepository(
                new PasswordToken,
                $this->app->make('log'),
                $this->app->make(Dispatcher::class),
                $this->app->make(TokenGenerationServiceContract::class)
            );
        });
        $this->app->bind(PaymentRepositoryContract::class, function () {
            return new PaymentRepository(
                new Payment,
                $this->app->make('log'),
                $this->app->make(LineItemRepositoryContract::class)
            );
        });
        $this->app->bind(PaymentMethodRepositoryContract::class, function () {
            return new PaymentMethodRepository(
                new PaymentMethod,
                $this->app->make('log')
            );
        });
        $this->app->bind(ProfileImageRepositoryContract::class, function () {
            return new ProfileImageRepository(
                new ProfileImage,
                $this->app->make('log'),
                $this->app->make(FilesystemManager::class),
                $this->app->make(AssetConfigurationServiceContract::class)
            );
        });
        $this->app->bind(PushTemplateRepositoryContract::class, function () {
            return new PushTemplateRepository(
                new PushTemplate,
                $this->app->make('log'),
            );
        });
        $this->app->bind(ResourceRepositoryContract::class, function () {
            return new ResourceRepository(
                new Resource,
                $this->app->make('log')
            );
        });
        $this->app->bind(RoleRepositoryContract::class, function () {
            return new RoleRepository(
                new Role,
                $this->app->make('log')
            );
        });
        $this->app->bind(StatisticRepositoryContract::class, function () {
            return new StatisticRepository(
                new Statistic,
                $this->app->make('log'),
                $this->app->make(StatisticFilterRepository::class),
                $this->app->make(Dispatcher::class)
            );
        });
        $this->app->bind(SubscriptionRepositoryContract::class, function () {
            return new SubscriptionRepository(
                new Subscription,
                $this->app->make('log'),
                $this->app->make(MembershipPlanRateRepositoryContract::class)
            );
        });
        $this->app->bind(TargetStatisticRepositoryContract::class, function () {
            return new TargetStatisticRepository(
                new TargetStatistic,
                $this->app->make('log')
            );
        });
        $this->app->bind(ThreadRepositoryContract::class, function () {
            return new ThreadRepository(
                new Thread,
                $this->app->make('log')
            );
        });
        $this->app->bind(UserPageRepositoryContract::class, function () {
            return new UserPageRepository(
                new UserPage,
                $this->app->make('log')
            );
        });
        $this->app->bind(UserPageComponentRepositoryContract::class, function () {
            return new UserPageComponentRepository(
                new UserPageComponent,
                $this->app->make('log')
            );
        });
        $this->app->bind(UserRepositoryContract::class, function () {
            return new UserRepository(
                new User,
                $this->app->make('log'),
                $this->app->make(Hasher::class),
                $this->app->make(Repository::class)
            );
        });
        $this->app->bind(VoteRepositoryContract::class, function () {
            return new VoteRepository(
                new Vote,
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
