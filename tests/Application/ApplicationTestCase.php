<?php

declare(strict_types=1);

namespace Polis\Tests\Application;

use App\Models\Role;
use App\Models\User\User;
use App\Providers\AppRepositoryProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AppValidatorProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\RouteServiceProvider;
use Cartalyst\Stripe\Laravel\StripeServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider;
use Polis\Exceptions\Handler;
use Polis\Http\Middleware\ExpandParsingMiddleware;
use Polis\Http\Middleware\Issue404IfPageAfterPaginationMiddleware;
use Polis\Http\Middleware\JWTGetUserFromTokenProtectedRouteMiddleware;
use Polis\Http\Middleware\JWTGetUserFromTokenUnprotectedRouteMiddleware;
use Polis\Http\Middleware\LogMiddleware;
use Polis\Http\Middleware\SearchFilterParsingMiddleware;
use Polis\Tests\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Base test case for the dummy consumer application shipped under
 * tests/Application. This boots the real Polis service provider stack the
 * way the PolisOS API (the canonical consumer of this package) does, so the
 * ported Feature/Integration HTTP tests can actually exercise the package's
 * abstract controllers, requests, policies, repositories and routes.
 *
 * The existing {@see TestCase} deliberately registers NO
 * providers (the Unit suite exercises library code in isolation). This class
 * is a SEPARATE base that opts in to the full application boot, so the Unit
 * suite's contract is untouched.
 */
abstract class ApplicationTestCase extends OrchestraTestCase
{
    /**
     * The user the current test is authenticated as.
     *
     * @var User
     */
    protected $actingAs;

    /**
     * Register the consumer application's service providers, mirroring the
     * PolisOS API's provider list.
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelServiceProvider::class,
            StripeServiceProvider::class,
            AppServiceProvider::class,
            AppRepositoryProvider::class,
            AppValidatorProvider::class,
            AuthServiceProvider::class,
            EventServiceProvider::class,
            RouteServiceProvider::class,
        ];
    }

    /**
     * Define the application environment: JWT secret, auth guards/providers,
     * the api-v1 middleware group and the jwt.auth.* aliases the routes rely
     * on. Mirrors PolisOS's config/auth.php + app/Http/Kernel.php.
     */
    protected function defineEnvironment($app): void
    {
        // BaseRouteServiceProvider::map() loads routes via
        // base_path('routes/api-v1.php'). Testbench points base_path at its
        // bundled laravel skeleton, so repoint it at the dummy app so the
        // ported PolisOS routes/ dir is used.
        $app->setBasePath(__DIR__);

        // Load the dummy app's lang overrides (older Laravel validation
        // phrasing the ported PolisOS tests assert on).
        $app->useLangPath(__DIR__.'/lang');

        // Use the package exception handler (PolisOS binds this in
        // bootstrap/app.php). It maps ValidationException -> 400, the JWT
        // exceptions -> 401, etc., which the ported tests assert on. Without
        // it Testbench's default handler returns 422/500.
        $app->singleton(
            ExceptionHandler::class,
            Handler::class,
        );

        $config = $app->make('config');

        $config->set('app.debug', (bool) env('APP_DEBUG', false));
        $config->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $config->set('app.url', 'http://localhost');
        // AssetConfigurationService requires a non-null server URL to build
        // asset paths; the ported PolisOS tests run with this configured.
        $config->set('app.asset_url', 'http://localhost');
        $config->set('database.default', 'testing');
        $config->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // JWT.
        $config->set('jwt.secret', 'testing-secret-testing-secret-testing');
        $config->set('jwt.ttl', 60);
        $config->set('jwt.algo', 'HS256');
        // Blacklist stays enabled (the logout endpoint calls
        // JWTAuth::invalidate(), which requires it).
        $config->set('jwt.blacklist_enabled', true);

        // Auth: the package ships a custom "user-authentication" provider
        // driver (registered by BaseAuthServiceProvider).
        $config->set('auth.defaults.guard', 'web');
        $config->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $config->set('auth.guards.api', [
            'driver' => 'token',
            'provider' => 'users',
        ]);
        $config->set('auth.providers.users', [
            'driver' => 'user-authentication',
            'model' => User::class,
        ]);

        // Polis package config (messaging services off in tests).
        $config->set('polis.messaging_services.slack_enabled', false);
        $config->set('polis.messaging_services.sms_enabled', false);
        $config->set('polis.messaging_services.push_enabled', false);

        // Route middleware: register the api-v1 group + jwt aliases exactly as
        // PolisOS's Http/Kernel does. Testbench has no App\Http\Kernel.
        $router = $app->make('router');

        $router->aliasMiddleware('jwt.auth.unprotected', JWTGetUserFromTokenUnprotectedRouteMiddleware::class);
        $router->aliasMiddleware('jwt.auth.protected', JWTGetUserFromTokenProtectedRouteMiddleware::class);

        $router->middlewareGroup('api-v1', [
            SubstituteBindings::class,
            LogMiddleware::class,
            Issue404IfPageAfterPaginationMiddleware::class,
            SearchFilterParsingMiddleware::class,
            ExpandParsingMiddleware::class,
        ]);
    }

    /**
     * Point Testbench at the dummy application's base path so
     * base_path('routes/api-v1.php') (used by BaseRouteServiceProvider::map())
     * resolves to tests/Application/routes.
     */
    protected function getBasePath(): string
    {
        return __DIR__;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->setupDatabase();
    }

    /**
     * Seed the fixed role rows the policies + factories reference. The
     * consolidated schema migration is create-only (no data), and the
     * PolisOS historical role seeds live inside migrations we don't replay,
     * so seed the canonical role ids here.
     */
    protected function seedRoles(): void
    {
        $roles = [
            Role::APP_USER => 'A Basic App User',
            Role::SUPER_ADMIN => 'A Super Admin',
            Role::ARTICLE_VIEWER => 'An Article Viewer',
            Role::ARTICLE_EDITOR => 'An Article Editor',
            Role::ADMINISTRATOR => 'Organization Admin',
            Role::MANAGER => 'Organization Manager',
            Role::CONTENT_EDITOR => 'Content Editor',
            Role::SUPPORT_STAFF => 'Support Staff',
        ];

        foreach ($roles as $id => $name) {
            DB::table('roles')->insertOrIgnore([
                'id' => $id,
                'name' => $name,
            ]);
        }
    }

    /**
     * Load every migration the ported tests touch: the PolisOS base schema
     * plus the package's own incremental migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/database/migrations');
    }

    /**
     * Provided for compatibility with tests that call it explicitly (the
     * ported PolisOS tests invoke $this->setupDatabase() in setUp()).
     * Migrations are already run by defineDatabaseMigrations(); this is a
     * no-op safety hook.
     */
    protected function setupDatabase(): void
    {
        // Intentionally empty: defineDatabaseMigrations() runs migrations and
        // Testbench wraps each test in a transaction via RefreshDatabase-style
        // in-memory sqlite. Kept so ported tests can call it without error.
    }

    /**
     * Authenticate as a freshly created user.
     */
    protected function actAsUser($data = []): void
    {
        $this->actingAs = User::factory()->create($data);
        $this->actingAs($this->actingAs);
    }

    /**
     * Authenticate as a freshly created user carrying the given role.
     */
    protected function actAs(int $roleId): void
    {
        $this->actAsUser();
        $this->actingAs->addRole($roleId);
    }

    /**
     * A log mock that swallows everything. Ported repository/integration tests
     * inject this into repositories under test.
     */
    protected function getGenericLogMock()
    {
        $logMock = mock(LoggerInterface::class);
        $logMock->shouldReceive('info');
        $logMock->shouldReceive('debug');
        $logMock->shouldReceive('warning');
        $logMock->shouldReceive('error');

        return $logMock;
    }

    /**
     * Temporarily enable debug output on a test (parity with the old base).
     */
    protected function enableDebug(): void
    {
        config(['app.debug' => true]);
    }
}
