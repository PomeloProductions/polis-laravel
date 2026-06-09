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
use Polis\Contracts\Repositories\Messaging\EmailTemplateRepositoryContract;
use Polis\Contracts\Repositories\Messaging\PushTemplateRepositoryContract;
use Polis\Contracts\Repositories\Payment\LineItemRepositoryContract;
use Polis\Contracts\Repositories\Payment\PaymentRepositoryContract;
use Polis\Contracts\Repositories\Statistic\StatisticRepositoryContract;
use Polis\Contracts\Repositories\Statistic\TargetStatisticRepositoryContract;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Contracts\Services\ArchiveHelperServiceContract;
use Polis\Contracts\Services\Asset\AssetConfigurationServiceContract;
use Polis\Contracts\Services\Asset\AssetImportServiceContract;
use Polis\Contracts\Services\Collection\ItemInEntityCollectionServiceContract;
use Polis\Contracts\Services\DirectoryCopyServiceContract;
use Polis\Contracts\Services\EntitySubscriptionCreationServiceContract;
use Polis\Contracts\Services\Indexing\ResourceRepositoryServiceContract;
use Polis\Contracts\Services\Messaging\EmailTemplateRenderingServiceContract;
use Polis\Contracts\Services\Messaging\MessageSendingSelectionServiceContract;
use Polis\Contracts\Services\Messaging\PushTemplateRenderingServiceContract;
use Polis\Contracts\Services\Messaging\SendEmailServiceContract;
use Polis\Contracts\Services\Messaging\SendPushNotificationServiceContract;
use Polis\Contracts\Services\Messaging\SendSlackNotificationServiceContract;
use Polis\Contracts\Services\Messaging\SendSMSServiceContract;
use Polis\Contracts\Services\ModelCacheServiceContract;
use Polis\Contracts\Services\ProratingCalculationServiceContract;
use Polis\Contracts\Services\Relations\RelationTraversalServiceContract;
use Polis\Contracts\Services\Statistic\StatisticSynchronizationServiceContract;
use Polis\Contracts\Services\Statistic\TargetStatisticProcessingServiceContract;
use Polis\Contracts\Services\StringHelperServiceContract;
use Polis\Contracts\Services\StripeCustomerServiceContract;
use Polis\Contracts\Services\StripePaymentServiceContract;
use Polis\Contracts\Services\TokenGenerationServiceContract;
use Polis\Contracts\Services\Wiki\ArticleVersionCalculationServiceContract;
use Polis\Mail\DefaultEmailTemplates;
use Polis\Push\DefaultPushTemplates;
use Polis\Services\ArchiveHelperService;
use Polis\Services\Asset\AssetConfigurationService;
use Polis\Services\Asset\AssetImportService;
use Polis\Services\BaseModelCacheService;
use Polis\Services\Collection\ItemInEntityCollectionService;
use Polis\Services\DirectoryCopyService;
use Polis\Services\EntitySubscriptionCreationService;
use Polis\Services\Messaging\EmailTemplateRenderingService;
use Polis\Services\Messaging\MessageSendingSelectionService;
use Polis\Services\Messaging\MessageSendingServiceNotImplemented;
use Polis\Services\Messaging\PushTemplateRenderingService;
use Polis\Services\Messaging\SendEmailService;
use Polis\Services\Messaging\SendPushNotificationService;
use Polis\Services\Messaging\SendSlackNotificationService;
use Polis\Services\Messaging\SendSMSNotificationService;
use Polis\Services\NoopStripeCustomerService;
use Polis\Services\ProratingCalculationService;
use Polis\Services\Relations\RelationTraversalService;
use Polis\Services\Statistic\StatisticSynchronizationService;
use Polis\Services\Statistic\TargetStatisticProcessingService;
use Polis\Services\StringHelperService;
use Polis\Services\StripePaymentService;
use Polis\Services\TokenGenerationService;
use Polis\Services\Wiki\ArticleVersionCalculationService;

/**
 * Base service provider for polis-laravel.
 *
 * Auto-bind behaviour
 * -------------------
 * Where possible, this provider resolves `App\...` consumer overrides and
 * falls back to a `Polis\...` package concrete via
 * {@see BaseServiceProvider::resolveConsumerOrPackage()}. The intent is to
 * let consumer applications drop empty shim classes that previously only
 * existed to put a name at the right FQN.
 *
 * Auto-bound (no consumer shim required):
 *  - App\Models\Messaging\Message -> Polis\Models\Messaging\Message
 *
 * Still requires a consumer-side concrete (the Polis class is abstract):
 *  - App\Services\Indexing\ResourceRepositoryService must extend
 *    Polis\Services\Indexing\BaseResourceRepositoryService. Missing this
 *    class throws a clear RuntimeException at bind time rather than failing
 *    silently.
 */
abstract class BaseServiceProvider extends ServiceProvider
{
    /**
     * Resolve a class name preferring a consumer-app override over the
     * package-provided concrete fallback.
     *
     * This is the core of polis-laravel's auto-bind behaviour: a consumer
     * application may supply its own `App\...` subclass to override a
     * package concrete, but if it does not, the provider falls back to the
     * `Polis\...` concrete shipped with this package.
     *
     * @param  class-string  $appClass  Fully-qualified `App\...` class to prefer.
     * @param  class-string  $polisClass  Fully-qualified `Polis\...` concrete to fall back to.
     * @return class-string The class name to use for binding/instantiation.
     */
    public static function resolveConsumerOrPackage(string $appClass, string $polisClass): string
    {
        return class_exists($appClass) ? $appClass : $polisClass;
    }

    public function provides(): array
    {
        return array_merge([
            ArchiveHelperServiceContract::class,
            ArticleVersionCalculationServiceContract::class,
            AssetConfigurationServiceContract::class,
            AssetImportServiceContract::class,
            DirectoryCopyServiceContract::class,
            EmailTemplateRenderingServiceContract::class,
            EntitySubscriptionCreationServiceContract::class,
            ItemInEntityCollectionServiceContract::class,
            MessageSendingSelectionServiceContract::class,
            ModelCacheServiceContract::class,
            ProratingCalculationServiceContract::class,
            PushTemplateRenderingServiceContract::class,
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
        $this->app->bind(EmailTemplateRenderingServiceContract::class, fn () => new EmailTemplateRenderingService(
            $this->app->make(EmailTemplateRepositoryContract::class),
            DefaultEmailTemplates::TEMPLATES,
        ));
        $this->app->bind(EntitySubscriptionCreationServiceContract::class, fn () => new EntitySubscriptionCreationService(
            $this->app->make(ProratingCalculationServiceContract::class),
            $this->app->make(SubscriptionRepositoryContract::class),
            $this->app->make(StripePaymentServiceContract::class),
        )
        );
        $this->app->bind(ItemInEntityCollectionServiceContract::class, fn () => new ItemInEntityCollectionService
        );
        $messageClass = self::resolveConsumerOrPackage(
            Message::class,
            \Polis\Models\Messaging\Message::class,
        );
        $this->app->bind(MessageSendingSelectionServiceContract::class, fn () => new MessageSendingSelectionService([
            $messageClass::VIA_EMAIL => $this->app->make(SendEmailServiceContract::class),
            $messageClass::VIA_SMS => $this->app->make(SendSMSServiceContract::class),
            $messageClass::VIA_PUSH_NOTIFICATION => $this->app->make(SendPushNotificationServiceContract::class),
            $messageClass::VIA_SLACK => $this->app->make(SendSlackNotificationServiceContract::class),
        ])
        );
        // The generic ModelCacheServiceContract needs a per-model cache key,
        // so the package can't pick a sensible default — consumers bind
        // their own model-specific subclass of BaseModelCacheService
        // against a model-specific contract. The binding below is a
        // signpost: it surfaces the misuse with a clear message instead of
        // a cryptic "no entry found for ..." container failure.
        $this->app->bind(ModelCacheServiceContract::class, function () {
            throw new \RuntimeException(
                'polis-laravel: ModelCacheServiceContract is abstract — bind a '
                .'concrete subclass of '.BaseModelCacheService::class.' against '
                .'a model-specific contract in your application service provider.'
            );
        });
        $this->app->bind(ProratingCalculationServiceContract::class, fn () => new ProratingCalculationService
        );
        $this->app->bind(PushTemplateRenderingServiceContract::class, fn () => new PushTemplateRenderingService(
            $this->app->make(PushTemplateRepositoryContract::class),
            DefaultPushTemplates::TEMPLATES,
        ));
        $this->app->bind(ResourceRepositoryServiceContract::class, function () {
            // ResourceRepositoryService has no concrete Polis fallback —
            // BaseResourceRepositoryService is abstract because consumer
            // applications must enumerate their own indexable resource
            // repositories. Consumers MUST provide:
            //   App\Services\Indexing\ResourceRepositoryService
            // extending Polis\Services\Indexing\BaseResourceRepositoryService.
            if (! class_exists(ResourceRepositoryService::class)) {
                throw new \RuntimeException(
                    'polis-laravel: missing consumer-side concrete '
                    .'App\\Services\\Indexing\\ResourceRepositoryService. '
                    .'Extend Polis\\Services\\Indexing\\BaseResourceRepositoryService '
                    .'and define availableResourceRepositories().'
                );
            }

            $class = ResourceRepositoryService::class;

            return new $class($this->app);
        });
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
        // Default to a no-op so consumer apps that don't use Stripe can boot
        // without registering a real implementation. Consumers that DO want
        // Stripe should register their own binding to StripeCustomerServiceContract
        // (e.g. \Polis\Services\StripeCustomerService) BEFORE this provider runs,
        // or in a subclass's registerApp(); bindIf() leaves their binding intact.
        $this->app->bindIf(StripeCustomerServiceContract::class, fn () => new NoopStripeCustomerService);
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
