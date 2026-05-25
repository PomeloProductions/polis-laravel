<?php

declare(strict_types=1);

namespace Polis\Providers;

use App\Models\Messaging\Message;
use App\Services\Indexing\ResourceRepositoryService;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use GuzzleHttp\Client;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\Twilio\Twilio;
use Polis\Contracts\Repositories\AssetRepositoryContract;
use Polis\Contracts\Repositories\Organization\OrganizationRepositoryContract;
use Polis\Contracts\Repositories\Payment\LineItemRepositoryContract;
use Polis\Contracts\Repositories\Payment\PaymentMethodRepositoryContract;
use Polis\Contracts\Repositories\Payment\PaymentRepositoryContract;
use Polis\Contracts\Repositories\Statistic\StatisticRepositoryContract;
use Polis\Contracts\Repositories\Statistic\TargetStatisticRepositoryContract;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Contracts\Services\ArchiveHelperServiceContract;
use Polis\Contracts\Services\Asset\AssetConfigurationServiceContract;
use Polis\Contracts\Services\Asset\AssetImportServiceContract;
use Polis\Contracts\Services\Collection\ItemInEntityCollectionServiceContract;
use Polis\Contracts\Services\DirectoryCopyServiceContract;
use Polis\Contracts\Services\EntitySubscriptionCreationServiceContract;
use Polis\Contracts\Services\Indexing\ResourceRepositoryServiceContract;
use Polis\Contracts\Services\Messaging\MessageSendingSelectionServiceContract;
use Polis\Contracts\Services\Messaging\SendEmailServiceContract;
use Polis\Contracts\Services\Messaging\SendPushNotificationServiceContract;
use Polis\Contracts\Services\Messaging\SendSlackNotificationServiceContract;
use Polis\Contracts\Services\Messaging\SendSMSServiceContract;
use Polis\Contracts\Services\ProratingCalculationServiceContract;
use Polis\Contracts\Services\Relations\RelationTraversalServiceContract;
use Polis\Contracts\Services\Statistic\StatisticSynchronizationServiceContract;
use Polis\Contracts\Services\Statistic\TargetStatisticProcessingServiceContract;
use Polis\Contracts\Services\StringHelperServiceContract;
use Polis\Contracts\Services\StripeCustomerServiceContract;
use Polis\Contracts\Services\StripePaymentServiceContract;
use Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract;
use Polis\Contracts\Repositories\User\UserPageRepositoryContract;
use Polis\Contracts\Services\TokenGenerationServiceContract;
use Polis\Contracts\Services\Wiki\ArticleVersionCalculationServiceContract;
use Polis\Services\ArchiveHelperService;
use Polis\Services\Asset\AssetConfigurationService;
use Polis\Services\Asset\AssetImportService;
use Polis\Services\Collection\ItemInEntityCollectionService;
use Polis\Services\DirectoryCopyService;
use Polis\Services\EntitySubscriptionCreationService;
use Polis\Services\Messaging\MessageSendingSelectionService;
use Polis\Services\Messaging\MessageSendingServiceNotImplemented;
use Polis\Services\Messaging\SendEmailService;
use Polis\Services\Messaging\SendPushNotificationService;
use Polis\Services\Messaging\SendSlackNotificationService;
use Polis\Services\Messaging\SendSMSNotificationService;
use Polis\Services\ProratingCalculationService;
use Polis\Services\Relations\RelationTraversalService;
use Polis\Services\Statistic\StatisticSynchronizationService;
use Polis\Services\Statistic\TargetStatisticProcessingService;
use Polis\Services\StringHelperService;
use Polis\Services\StripeCustomerService;
use Polis\Services\StripePaymentService;
use Polis\Services\TokenGenerationService;
use Polis\Services\Wiki\ArticleVersionCalculationService;

abstract class BaseServiceProvider extends ServiceProvider
{
    public function provides(): array
    {
        return array_merge([
            ArchiveHelperServiceContract::class,
            ArticleVersionCalculationServiceContract::class,
            AssetConfigurationServiceContract::class,
            AssetImportServiceContract::class,
            DirectoryCopyServiceContract::class,
            EntitySubscriptionCreationServiceContract::class,
            ItemInEntityCollectionServiceContract::class,
            MessageSendingSelectionServiceContract::class,
            ProratingCalculationServiceContract::class,
            RelationTraversalServiceContract::class,
            ResourceRepositoryServiceContract::class,
            SendEmailServiceContract::class,
            SendPushNotificationServiceContract::class,
            SendSlackNotificationServiceContract::class,
            SendSMSServiceContract::class,
            StatisticSynchronizationServiceContract::class,
            StringHelperServiceContract::class,
            StripeCustomerServiceContract::class,
            StripePaymentServiceContract::class,
            TargetStatisticProcessingServiceContract::class,
            TokenGenerationServiceContract::class,
        ], $this->appProviders());
    }

    /**
     * All app specific repositories that are provided here
     */
    abstract public function appProviders(): array;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerEnvironmentSpecificProviders();

        $this->app->bind(ArchiveHelperServiceContract::class, fn () => new ArchiveHelperService(
            new \ZipArchive,
        )
        );
        $this->app->bind(ArticleVersionCalculationServiceContract::class, fn () => new ArticleVersionCalculationService
        );
        $this->app->bind(AssetConfigurationServiceContract::class, fn () => new AssetConfigurationService(
            $this->app->make('config')->get('app.asset_url'),
            'assets'
        )
        );
        $this->app->bind(AssetImportServiceContract::class, fn () => new AssetImportService(
            $this->app->make(AssetRepositoryContract::class),
            new Client,
        )
        );
        $this->app->bind(DirectoryCopyServiceContract::class, fn () => new DirectoryCopyService
        );
        $this->app->bind(EntitySubscriptionCreationServiceContract::class, fn () => new EntitySubscriptionCreationService(
            $this->app->make(ProratingCalculationServiceContract::class),
            $this->app->make(SubscriptionRepositoryContract::class),
            $this->app->make(StripePaymentServiceContract::class),
        )
        );
        $this->app->bind(ItemInEntityCollectionServiceContract::class, fn () => new ItemInEntityCollectionService
        );
        $this->app->bind(MessageSendingSelectionServiceContract::class, fn () => new MessageSendingSelectionService([
            Message::VIA_EMAIL => $this->app->make(SendEmailServiceContract::class),
            Message::VIA_SMS => $this->app->make(SendSMSServiceContract::class),
            Message::VIA_PUSH_NOTIFICATION => $this->app->make(SendPushNotificationServiceContract::class),
            Message::VIA_SLACK => $this->app->make(SendSlackNotificationServiceContract::class),
        ])
        );
        $this->app->bind(ProratingCalculationServiceContract::class, fn () => new ProratingCalculationService
        );
        $this->app->bind(ResourceRepositoryServiceContract::class, fn () => new ResourceRepositoryService($this->app)
        );
        $this->app->bind(SendEmailServiceContract::class, fn () => new SendEmailService($this->app->make(Mailer::class))
        );
        $this->app->bind(SendPushNotificationServiceContract::class, function () {
            if (config('polis.messaging_services.push_enabled', false)) {
                return new SendPushNotificationService(
                    config('app.services.fcm,key', ''),
                    new Client,
                    $this->app->make('log'),
                );
            } else {
                return new class extends MessageSendingServiceNotImplemented implements SendPushNotificationServiceContract {};
            }
        });
        $this->app->bind(SendSlackNotificationServiceContract::class, function () {
            if (config('polis.messaging_services.slack_enabled', false)) {
                return new SendSlackNotificationService;
            } else {
                return new class extends MessageSendingServiceNotImplemented implements SendSlackNotificationServiceContract {};
            }
        });
        $this->app->bind(SendSMSServiceContract::class, function () {
            if (config('polis.messaging_services.sms_enabled', false)) {
                return new SendSMSNotificationService(
                    $this->app->make(Twilio::class),
                    $this->app->make('log'),
                );
            } else {
                return new class extends MessageSendingServiceNotImplemented implements SendSMSServiceContract {};
            }
        });
        $this->app->bind(StringHelperServiceContract::class, fn () => new StringHelperService
        );
        $this->app->bind(StripeCustomerServiceContract::class, fn () => new StripeCustomerService(
            $this->app->make(UserRepositoryContract::class),
            $this->app->make(OrganizationRepositoryContract::class),
            $this->app->make(PaymentMethodRepositoryContract::class),
            $this->app->make('stripe')->customers(),
            $this->app->make('stripe')->cards(),
        )
        );
        $this->app->bind(StripePaymentServiceContract::class, function () {
            $stripe = $this->app->make('stripe');

            return new StripePaymentService(
                $this->app->make(PaymentRepositoryContract::class),
                $this->app->make(LineItemRepositoryContract::class),
                $this->app->make(Dispatcher::class),
                $stripe->charges(),
                $stripe->refunds(),
                $this->app->make('log')
            );
        });
        $this->app->bind(RelationTraversalServiceContract::class, fn () => new RelationTraversalService
        );
        $this->app->bind(StatisticSynchronizationServiceContract::class, fn () => new StatisticSynchronizationService(
            $this->app->make(StatisticRepositoryContract::class),
            $this->app->make(TargetStatisticRepositoryContract::class)
        )
        );
        $this->app->bind(TargetStatisticProcessingServiceContract::class, fn () => new TargetStatisticProcessingService(
            $this->app->make(RelationTraversalServiceContract::class),
            $this->app->make(TargetStatisticRepositoryContract::class)
        )
        );
        $this->app->bind(TokenGenerationServiceContract::class, fn () => new TokenGenerationService
        );
        $this->registerApp();
    }

    /**
     * Registers any environment specific rpviders
     */
    public function registerEnvironmentSpecificProviders(): void
    {
        if ($this->app->environment() == 'local') {
            $this->app->register(IdeHelperServiceProvider::class);
        }
    }

    /**
     * Runs any app specific registrations
     */
    abstract public function registerApp(): void;
}
