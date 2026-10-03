<?php

declare(strict_types=1);

namespace Polis\Plugins;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Boots the Polis plugin system with ZERO consumer wiring.
 *
 * Auto-registered via composer's `extra.laravel.providers`, so simply
 * requiring polis-laravel activates it. A consumer that defines no
 * `config/plugins.php` (or an empty `enabled` array) gets a complete no-op —
 * the provider is fully backwards-compatible and additive.
 *
 * How plugins take effect without hand-editing the Base* providers
 * ----------------------------------------------------------------
 * This provider owns a singleton {@see PluginManager} and drives the full
 * lifecycle itself:
 *  - register(): merges the default config/plugins.php, then applies plugin
 *    container bindings (resolveConsumerOrPackage-aware, so consumer overrides
 *    still win).
 *  - boot(): applies plugin migrations (loadMigrationsFrom — closing the
 *    migration-autoload gap for plugins), namespaced plugin config, validator
 *    extensions, Gate policies, model observers, and route groups.
 *
 * Composition with the existing Base* providers
 * ---------------------------------------------
 * Nothing here is double-registered through a Base* provider: the manager is
 * the single application point. The Base* providers remain untouched and keep
 * working exactly as before. Where a consumer DOES want plugin contributions
 * to flow through its own provider mapping (e.g. to make plugin listeners part
 * of an explicit EventServiceProvider::listens()), the manager exposes
 * aggregatedListeners()/aggregatedObservers()/aggregatedPolicies() as a
 * one-line merge hook — but that is optional; listeners are applied here by
 * registering them directly on the dispatcher.
 */
final class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Default config/plugins.php (ships ['enabled' => []]). A consumer that
        // publishes its own config/plugins.php overrides this; absence is a no-op.
        $this->mergeConfigFrom($this->packageConfigPath(), 'plugins');

        $this->app->singleton(PluginManager::class, fn ($app) => new PluginManager($app));

        // Plugin container bindings must land in register() before anything is
        // resolved, mirroring BaseServiceProvider::register().
        $this->manager()->applyBindings();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->packageConfigPath() => $this->app->configPath('plugins.php'),
            ], 'polis-plugins-config');
        }

        $manager = $this->manager();

        // Close the migration-autoload gap FOR PLUGINS: each plugin's migration
        // directory is loaded so its migrations just run on `migrate`.
        $manager->applyMigrations(fn (string $path) => $this->loadMigrationsFrom($path));

        // Namespaced plugin config under plugin.<key>.*
        $manager->applyConfig();

        // Validator rule extensions.
        $manager->applyValidators($this->app->make(ValidationFactory::class));

        // Gate policies.
        $manager->applyPolicies(function (string $model, string $policy): void {
            Gate::policy($model, $policy);
        });

        // Model observers.
        $manager->applyObservers();

        // Event listeners — registered directly on the dispatcher so plugins
        // work even without a consumer EventServiceProvider merge.
        foreach ($manager->aggregatedListeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                $this->app['events']->listen($event, $listener);
            }
        }

        // Plugin route groups.
        $manager->applyRoutes();
    }

    private function manager(): PluginManager
    {
        return $this->app->make(PluginManager::class);
    }

    /**
     * Absolute path to the package's default config/plugins.php.
     */
    private function packageConfigPath(): string
    {
        return dirname(__DIR__, 2).'/config/plugins.php';
    }
}
