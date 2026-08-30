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
use Polis\Http\Core\Requests\RequestResolver;
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
     * Platform default for jwt.blacklist_grace_period (seconds). A short window
     * that lets a just-rotated token still authenticate during concurrent
     * refreshes, preventing race-driven logouts. See applyJwtConfigGapFill().
     */
    public const DEFAULT_JWT_BLACKLIST_GRACE_PERIOD = 30;

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

    /**
     * Gap-fill the runtime redis configuration so the platform's redis env
     * (CACHE_STORE=redis / SESSION_DRIVER=redis / QUEUE_CONNECTION=redis +
     * REDIS_PREFIX) works on every consumer WITHOUT that consumer having to
     * edit its own config/database.php.
     *
     * Why this exists
     * ---------------
     * The Athenia-based consumer apps ship a stripped `config/database.php`
     * whose `redis` section defines ONLY a `default` connection and has no
     * `options.prefix`. But Laravel's stock `config/cache.php` hardcodes the
     * redis cache store to the `cache` connection. So the moment the platform
     * sets `CACHE_STORE=redis` those apps blow up with:
     *
     *   RedisManager: Redis connection [cache] not configured
     *
     * crashing queue workers / schedulers and 500ing cache & session
     * requests. On top of that `REDIS_PREFIX` was silently ignored (no
     * `options.prefix`), so tenant keys were NOT isolated inside the shared
     * redis. PolisOS was hotfixed directly in its own config/database.php
     * (PolisOS #40), but that is per-app — every other Athenia tenant would
     * hit the same wall. This fixes it once, in the package.
     *
     * How it behaves
     * --------------
     * Pure gap-fill: it only writes a key when the app has NOT already
     * defined it, so any app-provided redis config (including PolisOS #40's
     * explicit edit) is respected and left untouched. It does NOT touch the
     * cache/session/queue DRIVER values — the platform env controls those;
     * this only guarantees the redis CONNECTIONS + key prefix EXIST so those
     * drivers can resolve.
     *
     * It runs from register() (before redis is ever used) and mutates the
     * LIVE config repository, so it takes effect even when the consumer has
     * run `config:cache` — cached config is loaded into that same repository
     * instance, and these writes override it at runtime.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config  The live config repository to mutate.
     */
    public static function applyRedisConfigGapFill($config): void
    {
        // 1. Key prefix for tenant isolation in the shared redis. The Athenia
        //    stripped config has no `options.prefix`, so REDIS_PREFIX was
        //    ignored. Fill it only when the app hasn't set one (null/empty).
        $existingPrefix = $config->get('database.redis.options.prefix');
        if ($existingPrefix === null || $existingPrefix === '') {
            $config->set('database.redis.options.prefix', env('REDIS_PREFIX', ''));
        }

        // 2. The `cache` redis connection that config/cache.php points at when
        //    CACHE_STORE=redis. Define it from env — mirroring Laravel's stock
        //    `cache` connection — only if the app hasn't defined it already.
        //    Uses a separate logical DB (REDIS_CACHE_DB, default 1) so cache
        //    flushes don't wipe queue/session data on DB 0.
        if ($config->get('database.redis.cache') === null) {
            $config->set('database.redis.cache', [
                'url' => env('REDIS_URL'),
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'username' => env('REDIS_USERNAME'),
                'password' => env('REDIS_PASSWORD'),
                'port' => env('REDIS_PORT', '6379'),
                'database' => env('REDIS_CACHE_DB', '1'),
            ]);
        }

        // 3. Safety net: ensure a `default` redis connection exists (used by
        //    sessions/queues). The Athenia config usually ships this, but fill
        //    it if it's somehow missing. Never clobber an existing one.
        if ($config->get('database.redis.default') === null) {
            $config->set('database.redis.default', [
                'url' => env('REDIS_URL'),
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'username' => env('REDIS_USERNAME'),
                'password' => env('REDIS_PASSWORD'),
                'port' => env('REDIS_PORT', '6379'),
                'database' => env('REDIS_DB', '0'),
            ]);
        }
    }

    /**
     * Platform default for the JWT blacklist grace period.
     *
     * When a token is refreshed, jwt-auth blacklists the old one. With
     * tymon/jwt-auth's stock `blacklist_grace_period` of 0, the old token is
     * invalid the instant it is rotated — so two concurrent refreshes (React
     * StrictMode double-mount, a burst of requests all hitting an expired
     * access token at once) race: the second refresh presents a token the
     * first just blacklisted, gets a 401, and logs the user out. A short grace
     * window lets a just-rotated token keep working for a few seconds so those
     * concurrent refreshes don't self-invalidate.
     *
     * This is a platform-wide default so every consumer app benefits without
     * editing its own config/jwt.php. It:
     *   - honours an explicit JWT_BLACKLIST_GRACE_PERIOD env if the app sets one;
     *   - otherwise raises the value to {@see self::DEFAULT_JWT_BLACKLIST_GRACE_PERIOD}
     *     ONLY when the current config is still jwt-auth's stock 0 (i.e. nobody
     *     chose a value), so an app that deliberately set a different grace
     *     period is left untouched.
     *
     * Like applyRedisConfigGapFill(), it runs from register() and mutates the
     * LIVE config repository, so it takes effect even under `config:cache`.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config  The live config repository to mutate.
     */
    public static function applyJwtConfigGapFill($config): void
    {
        // App has no jwt config published at all — nothing to gap-fill against
        // (jwt-auth isn't installed / configured for this consumer).
        if ($config->get('jwt') === null) {
            return;
        }

        $envValue = env('JWT_BLACKLIST_GRACE_PERIOD');
        if ($envValue !== null && $envValue !== '') {
            $config->set('jwt.blacklist_grace_period', (int) $envValue);

            return;
        }

        // No env override: only bump the stock jwt-auth default of 0. Leave any
        // value an app deliberately chose (including a deliberate 0 via env,
        // which would have been handled above) untouched.
        if ((int) $config->get('jwt.blacklist_grace_period') === 0) {
            $config->set('jwt.blacklist_grace_period', self::DEFAULT_JWT_BLACKLIST_GRACE_PERIOD);
        }
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

        // Gap-fill the redis config (cache connection + tenant key prefix) so
        // the platform's redis env works on every consumer without each app
        // editing its own config/database.php. See applyRedisConfigGapFill().
        // Runs here in register() so it lands before redis is ever resolved.
        self::applyRedisConfigGapFill($this->app->make('config'));

        // Raise the JWT blacklist grace period platform-wide so concurrent
        // token refreshes don't self-invalidate (stock jwt-auth ships 0). See
        // applyJwtConfigGapFill(). Runs before the auth guard resolves a token.
        self::applyJwtConfigGapFill($this->app->make('config'));

        $this->registerEnvironmentSpecificProviders();

        // Let consumers omit empty App\Http\Core\Requests\... shims: the
        // abstract controllers type-hint the package's own concrete requests,
        // and this rebinds any package request that the consumer HAS chosen to
        // override (App\Http\Core\Requests\...) so the override is injected
        // instead. Requests without an override are left untouched and Laravel
        // instantiates the package concrete directly.
        RequestResolver::registerBindings($this->app, RequestResolver::packageRequests());

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
        // The generic period generator depends on a PeriodComponentCopierContract.
        // The package ships no default copier (that was Todo-specific); a consumer
        // binds its own implementation. The binding stays lazy so the generic
        // engine is available to any consumer that supplies a copier.
        $this->app->bind(
            PeriodGenerationServiceContract::class,
            fn () => new PeriodPageGenerationService(
                $this->app->make(UserPageRepositoryContract::class),
                $this->app->make(PeriodComponentCopierContract::class),
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
