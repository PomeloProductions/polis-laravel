<?php

declare(strict_types=1);

namespace Polis\Providers;

use App\Models\Messaging\Message;
use App\Services\Indexing\ResourceRepositoryService;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use GuzzleHttp\Client;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Contract\Messaging;
use NotificationChannels\Twilio\Twilio;
use Polis\Contracts\Repositories\AssetRepositoryContract;
use Polis\Contracts\Repositories\Messaging\EmailTemplateRepositoryContract;
use Polis\Contracts\Repositories\Messaging\PushTemplateRepositoryContract;
use Polis\Contracts\Repositories\Payment\LineItemRepositoryContract;
use Polis\Contracts\Repositories\Payment\PaymentRepositoryContract;
use Polis\Contracts\Repositories\Statistic\StatisticRepositoryContract;
use Polis\Contracts\Repositories\Statistic\TargetStatisticRepositoryContract;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Contracts\Repositories\User\TodoSettingRepositoryContract;
use Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract;
use Polis\Contracts\Repositories\User\UserPageRepositoryContract;
use Polis\Contracts\Services\ArchiveHelperServiceContract;
use Polis\Contracts\Services\Asset\AssetConfigurationServiceContract;
use Polis\Contracts\Services\Asset\AssetImportServiceContract;
use Polis\Contracts\Services\Collection\ItemInEntityCollectionServiceContract;
use Polis\Contracts\Services\DirectoryCopyServiceContract;
use Polis\Contracts\Services\EntitySubscriptionCreationServiceContract;
use Polis\Contracts\Services\ExternalRateLimiterContract;
use Polis\Contracts\Services\Indexing\ResourceRepositoryServiceContract;
use Polis\Contracts\Services\InvitationUrlServiceContract;
use Polis\Contracts\Services\Messaging\EmailTemplateRenderingServiceContract;
use Polis\Contracts\Services\Messaging\MessageSendingSelectionServiceContract;
use Polis\Contracts\Services\Messaging\PushTemplateRenderingServiceContract;
use Polis\Contracts\Services\Messaging\SendEmailServiceContract;
use Polis\Contracts\Services\Messaging\SendPushNotificationServiceContract;
use Polis\Contracts\Services\Messaging\SendSlackNotificationServiceContract;
use Polis\Contracts\Services\Messaging\SendSMSServiceContract;
use Polis\Contracts\Services\ModelCacheServiceContract;
use Polis\Contracts\Services\PeriodComponentCopierContract;
use Polis\Contracts\Services\PeriodGenerationServiceContract;
use Polis\Contracts\Services\ProratingCalculationServiceContract;
use Polis\Contracts\Services\Relations\NodeTreeServiceContract;
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
use Polis\Services\ExternalRateLimiter;
use Polis\Services\InvitationUrlService;
use Polis\Services\Messaging\EmailTemplateRenderingService;
use Polis\Services\Messaging\MessageSendingSelectionService;
use Polis\Services\Messaging\MessageSendingServiceNotImplemented;
use Polis\Services\Messaging\PushTemplateRenderingService;
use Polis\Services\Messaging\SendEmailService;
use Polis\Services\Messaging\SendPushNotificationService;
use Polis\Services\Messaging\SendSlackNotificationService;
use Polis\Services\Messaging\SendSMSNotificationService;
use Polis\Services\NoopStripeCustomerService;
use Polis\Services\PeriodPageGenerationService;
use Polis\Services\ProratingCalculationService;
use Polis\Services\Relations\NodeTreeService;
use Polis\Services\Relations\RelationTraversalService;
use Polis\Services\Statistic\StatisticSynchronizationService;
use Polis\Services\Statistic\TargetStatisticProcessingService;
use Polis\Services\StringHelperService;
use Polis\Services\StripePaymentService;
use Polis\Services\Todo\TodoGenerationService;
use Polis\Services\Todo\TodoPeriodComponentCopier;
use Polis\Services\Todo\TodoPeriodLadder;
use Polis\Services\Todo\TodoTaskTreeService;
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
            ExternalRateLimiterContract::class,
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
     * Absolute path to the package's default `config/polis.php` shipped
     * with this library. Centralized so register() (merge) and boot()
     * (publish) cannot drift.
     */
    private function packageConfigPath(): string
    {
        return dirname(__DIR__, 2).'/config/polis.php';
    }

    /**
     * Ensure the framework's database connections honor a full connection
     * string supplied via the `DATABASE_URL` environment variable.
     *
     * The client-driver controller injects tenant DB credentials as a single
     * `DATABASE_URL` (e.g. `mysql://user:pass@host:25060/db`). Laravel only
     * parses such a URL when the target connection config carries a `url`
     * key (see {@see ConfigurationUrlParser}). A plain
     * Laravel app's shipped `config/database.php` DOES include that key, but
     * consumers can drift, and older/customized app configs may omit it —
     * in which case Laravel silently falls back to the discrete `DB_*`
     * defaults (`forge@127.0.0.1`) and every tenant deploy fails with
     * "Connection refused".
     *
     * This backfills a `url` key on the standard `mysql`/`pgsql` connections
     * from `DATABASE_URL`, but ONLY when:
     *   - the `DATABASE_URL` env var is actually set, AND
     *   - the connection exists, AND
     *   - the connection does not already define a (non-empty) `url`.
     *
     * It is therefore fully additive and non-breaking:
     *   - No `DATABASE_URL` set  → nothing changes; discrete `DB_*` still win.
     *   - App already sets `url` → we leave it untouched.
     */
    private function honorDatabaseUrl(): void
    {
        $config = $this->app->make('config');

        $connections = $config->get('database.connections');

        $overrides = self::resolveDatabaseUrlOverrides(
            env('DATABASE_URL'),
            is_array($connections) ? $connections : [],
        );

        foreach ($overrides as $connection => $url) {
            $config->set("database.connections.{$connection}.url", $url);
        }
    }

    /**
     * Pure resolution of which database connections should have their `url`
     * key backfilled from `DATABASE_URL`, extracted so the decision logic is
     * unit-testable without booting a full container.
     *
     * Returns a map of `connectionName => url` for every standard SQL
     * connection (`mysql`, `pgsql`) that is present in $connections and does
     * not already carry a non-empty `url`. Returns an empty array (i.e. a
     * no-op) when $databaseUrl is unset/blank, guaranteeing discrete `DB_*`
     * configuration keeps working untouched.
     *
     * @param  mixed  $databaseUrl  Raw `DATABASE_URL` env value.
     * @param  array<string, mixed>  $connections  The `database.connections` config array.
     * @return array<string, string> Map of connection name => URL to set.
     */
    public static function resolveDatabaseUrlOverrides(mixed $databaseUrl, array $connections): array
    {
        if (! is_string($databaseUrl) || $databaseUrl === '') {
            return [];
        }

        $overrides = [];

        foreach (['mysql', 'pgsql'] as $connection) {
            $definition = $connections[$connection] ?? null;

            // Only touch connections the consuming app actually defines.
            if (! is_array($definition)) {
                continue;
            }

            // Respect an explicit `url` the app has already configured.
            $existing = $definition['url'] ?? null;

            if (is_string($existing) && $existing !== '') {
                continue;
            }

            $overrides[$connection] = $databaseUrl;
        }

        return $overrides;
    }

    /**
     * Publish the package's config file so consumers can override the
     * defaults via their own `config/polis.php`.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->packageConfigPath() => $this->app->configPath('polis.php'),
            ], 'polis-config');
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge the package's default config so consumers that have not
        // published `config/polis.php` still get the defaults at runtime.
        $this->mergeConfigFrom($this->packageConfigPath(), 'polis');

        $this->honorDatabaseUrl();

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
        $this->app->bind(ExternalRateLimiterContract::class, fn () => new ExternalRateLimiter(
            $this->app->make(RateLimiter::class),
            $this->app->make(Repository::class),
            $this->app->make('log'),
            (int) (env('EXTERNAL_REQUEST_MIN_GAP_SECONDS', 20) ?: 20),
        )
        );
        $this->app->bind(InvitationUrlServiceContract::class, fn () => new InvitationUrlService(
            config('polis.invitations.accept_url_base'),
            (string) config('app.url', 'http://localhost'),
            (string) config('polis.invitations.accept_url_fallback_path', '/accept-invitation'),
            (string) config('polis.invitations.accept_url_token_param', 'invitation_token'),
        ));
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
                    $this->app->make(Messaging::class),
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

        // Generic period / node-tree framework.
        $this->app->bind(
            NodeTreeServiceContract::class,
            fn () => new NodeTreeService,
        );
        $this->app->bind(
            PeriodGenerationServiceContract::class,
            fn () => new PeriodPageGenerationService(
                $this->app->make(UserPageRepositoryContract::class),
                $this->app->make(PeriodComponentCopierContract::class),
            ),
        );

        // Todo module services (built on top of the generic framework).
        $this->app->bind(
            TodoTaskTreeService::class,
            fn () => new TodoTaskTreeService(
                $this->app->make(NodeTreeServiceContract::class),
            ),
        );
        $this->app->bind(
            TodoPeriodLadder::class,
            fn () => new TodoPeriodLadder(
                $this->app->make(TodoSettingRepositoryContract::class),
            ),
        );
        $this->app->bind(
            PeriodComponentCopierContract::class,
            fn () => new TodoPeriodComponentCopier(
                $this->app->make(UserPageComponentRepositoryContract::class),
                $this->app->make(TodoTaskTreeService::class),
            ),
        );
        $this->app->bind(
            TodoGenerationService::class,
            fn () => new TodoGenerationService(
                $this->app->make(PeriodGenerationServiceContract::class),
                $this->app->make(TodoPeriodLadder::class),
                $this->app->make(UserPageRepositoryContract::class),
            ),
        );

        $this->registerApp();
    }

    /**
     * Registers any environment specific providers
     */
    public function registerEnvironmentSpecificProviders(): void
    {
        if ($this->app->environment() == 'local'
            && class_exists(IdeHelperServiceProvider::class)
        ) {
            $this->app->register(IdeHelperServiceProvider::class);
        }
    }

    /**
     * Runs any app specific registrations
     */
    abstract public function registerApp(): void;
}
