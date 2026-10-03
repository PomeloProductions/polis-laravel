<?php

declare(strict_types=1);

namespace Polis\Plugins;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Polis\Providers\BaseServiceProvider;
use RuntimeException;

/**
 * Discovers enabled Polis plugins and applies their declared capabilities.
 *
 * Discovery
 * ---------
 * Reads `config('plugins.enabled', [])` — an array of {@see PluginContract}
 * class strings. Each is instantiated through the container, validated to be a
 * PluginContract, and asked to {@see PluginContract::register()} against a
 * fresh {@see DefaultPluginRegistrar}. Discovery is cached on the instance so
 * it runs once and is idempotent (`discover()` is a no-op after the first call
 * until {@see reset()}).
 *
 * Application
 * -----------
 * The manager is the single point that touches the container/router/dispatcher.
 * {@see PluginServiceProvider} calls:
 *  - {@see applyBindings()} in its register()
 *  - {@see applyMigrations()}, {@see applyConfig()}, {@see applyValidators()},
 *    {@see applyPolicies()} in its boot()
 *  - {@see applyRoutes()} in its map()
 *
 * Listeners and observers are exposed (aggregatedListeners()/aggregatedObservers())
 * for the event provider to merge; the manager also applies them directly in
 * {@see applyObservers()} so plugins work even without a consumer event provider.
 *
 * Determinism: plugins are processed in `config('plugins.enabled')` order and
 * de-duplicated by {@see PluginContract::key()} (first declaration wins).
 */
final class PluginManager
{
    /** @var array<int, PluginContract>|null Resolved, de-duplicated plugins (null until discovered). */
    private ?array $plugins = null;

    /** @var array<string, DefaultPluginRegistrar> Registrar per plugin key. */
    private array $registrars = [];

    public function __construct(private readonly Container $app) {}

    /**
     * Instantiate + register every enabled plugin. Idempotent: subsequent
     * calls return the cached result. Returns the resolved plugins.
     *
     * @return array<int, PluginContract>
     */
    public function discover(): array
    {
        if ($this->plugins !== null) {
            return $this->plugins;
        }

        $enabled = (array) $this->config()->get('plugins.enabled', []);

        $plugins = [];
        $seenKeys = [];

        foreach ($enabled as $pluginClass) {
            if (! is_string($pluginClass) || $pluginClass === '') {
                throw new InvalidArgumentException(
                    'plugins.enabled entries must be non-empty PluginContract class strings.'
                );
            }

            if (! class_exists($pluginClass)) {
                throw new RuntimeException("Enabled plugin [{$pluginClass}] does not exist.");
            }

            $plugin = $this->app->make($pluginClass);

            if (! $plugin instanceof PluginContract) {
                throw new RuntimeException(
                    "Enabled plugin [{$pluginClass}] must implement ".PluginContract::class.'.'
                );
            }

            $key = $plugin->key();

            // De-duplicate by key: first wins, keeping discovery deterministic.
            if (isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;

            $registrar = new DefaultPluginRegistrar($key);
            $plugin->register($registrar);

            $this->registrars[$key] = $registrar;
            $plugins[] = $plugin;
        }

        return $this->plugins = $plugins;
    }

    /**
     * Forget discovery so the next call re-reads config. Mainly for tests.
     */
    public function reset(): void
    {
        $this->plugins = null;
        $this->registrars = [];
    }

    /**
     * @return array<int, PluginContract>
     */
    public function plugins(): array
    {
        return $this->discover();
    }

    /**
     * A lightweight manifest of enabled plugins for introspection / frontend
     * parity checks.
     *
     * @return array<int, array{key: string, name: string, version: string, capabilities: array<int, string>}>
     */
    public function manifest(): array
    {
        return array_map(static fn (PluginContract $p): array => [
            'key' => $p->key(),
            'name' => $p->name(),
            'version' => $p->version(),
            'capabilities' => $p->capabilities(),
        ], $this->discover());
    }

    // ---------------------------------------------------------------------
    // Aggregation (read) — consumed by the Base* providers if they opt in.
    // ---------------------------------------------------------------------

    /**
     * Aggregated event => [listeners] across all plugins, for an event
     * provider to merge into its listens() mapping.
     *
     * @return array<class-string, array<int, class-string>>
     */
    public function aggregatedListeners(): array
    {
        $this->discover();

        $merged = [];
        foreach ($this->registrars as $registrar) {
            foreach ($registrar->getListeners() as $event => $listeners) {
                $existing = $merged[$event] ?? [];
                $merged[$event] = array_values(array_unique(array_merge($existing, $listeners)));
            }
        }

        return $merged;
    }

    /**
     * Aggregated model => observer map (concrete class strings resolved via
     * resolveConsumerOrPackage), for an event provider to merge.
     *
     * @return array<class-string, class-string>
     */
    public function aggregatedObservers(): array
    {
        $this->discover();

        $merged = [];
        foreach ($this->registrars as $registrar) {
            foreach ($registrar->getObservers() as $model => $observer) {
                $merged[$this->resolve($model)] = $this->resolve($observer);
            }
        }

        return $merged;
    }

    /**
     * Aggregated model => policy map (resolved), for an auth provider to merge.
     *
     * @return array<class-string, class-string>
     */
    public function aggregatedPolicies(): array
    {
        $this->discover();

        $merged = [];
        foreach ($this->registrars as $registrar) {
            foreach ($registrar->getPolicies() as $model => $policy) {
                $merged[$this->resolve($model)] = $this->resolve($policy);
            }
        }

        return $merged;
    }

    /**
     * Aggregated recorded entity-type declarations. STUB — no registry consumes
     * these yet; exposed for the follow-up that builds the entity-type registry.
     *
     * @return array<string, array<string, mixed>>
     */
    public function entityTypes(): array
    {
        $this->discover();

        $merged = [];
        foreach ($this->registrars as $registrar) {
            foreach ($registrar->getEntityTypes() as $type => $definition) {
                $merged[$type] = $definition;
            }
        }

        return $merged;
    }

    // ---------------------------------------------------------------------
    // Application (write) — called by PluginServiceProvider.
    // ---------------------------------------------------------------------

    /**
     * Bind every plugin-declared contract => concrete into the container.
     * A [App\..., Polis\...] pair is resolved via resolveConsumerOrPackage so
     * a consumer can still override a plugin binding. Closures/callables bind
     * as-is.
     */
    public function applyBindings(): void
    {
        $this->discover();

        foreach ($this->registrars as $registrar) {
            foreach ($registrar->getBindings() as $contract => $concrete) {
                if (is_callable($concrete)) {
                    $this->app->bind($contract, $concrete);

                    continue;
                }

                $this->app->bind($contract, $this->resolve($concrete));
            }
        }
    }

    /**
     * Register every plugin-declared observer against its model. Resolves the
     * model + observer via resolveConsumerOrPackage.
     */
    public function applyObservers(): void
    {
        foreach ($this->aggregatedObservers() as $model => $observer) {
            $model::observe($observer);
        }
    }

    /**
     * Load every plugin migration directory via loadMigrationsFrom on the
     * migrator, closing the migration-autoload gap FOR PLUGINS (the package's
     * own database/migrations remain un-autoloaded by design).
     */
    public function applyMigrations(callable $loadMigrationsFrom): void
    {
        $this->discover();

        $seen = [];
        foreach ($this->registrars as $registrar) {
            foreach ($registrar->getMigrations() as $path) {
                if (isset($seen[$path])) {
                    continue;
                }
                $seen[$path] = true;
                $loadMigrationsFrom($path);
            }
        }
    }

    /**
     * Merge each plugin's namespaced config under `plugin.<pluginKey>.<key>`
     * using mergeConfigFrom semantics (consumer values win; plugin defaults
     * fill the gaps).
     */
    public function applyConfig(): void
    {
        $this->discover();

        $config = $this->config();

        foreach ($this->registrars as $pluginKey => $registrar) {
            foreach ($registrar->getConfig() as $key => $default) {
                $configKey = "plugin.{$pluginKey}.{$key}";
                $existing = $config->get($configKey, []);
                // Existing (consumer) values take precedence over plugin defaults.
                $config->set($configKey, array_replace_recursive($default, is_array($existing) ? $existing : []));
            }
        }
    }

    /**
     * Extend the validator factory with every plugin-declared rule.
     */
    public function applyValidators(ValidationFactory $factory): void
    {
        $this->discover();

        foreach ($this->registrars as $registrar) {
            foreach ($registrar->getValidators() as $rule => $class) {
                $factory->extend($rule, $class);
            }
        }
    }

    /**
     * Merge aggregated plugin policies into Laravel's Gate.
     *
     * @param  callable(string, string): void  $registerPolicy  e.g. fn($model, $policy) => Gate::policy($model, $policy)
     */
    public function applyPolicies(callable $registerPolicy): void
    {
        foreach ($this->aggregatedPolicies() as $model => $policy) {
            $registerPolicy($model, $policy);
        }
    }

    /**
     * Register every plugin route file as its own middleware/prefix/namespace
     * group. Called from the route provider's map().
     */
    public function applyRoutes(): void
    {
        $this->discover();

        foreach ($this->registrars as $registrar) {
            foreach ($registrar->getRoutes() as $route) {
                $this->registerRouteGroup($route['path'], $route['opts']);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $opts
     */
    private function registerRouteGroup(string $path, array $opts): void
    {
        $group = Route::middleware($opts['middleware'] ?? 'api');

        if (isset($opts['prefix'])) {
            $group = $group->prefix($opts['prefix']);
        }

        if (isset($opts['namespace'])) {
            $group = $group->namespace($opts['namespace']);
        }

        $group->group($path);
    }

    /**
     * Resolve a concrete that may be a plain class string or a
     * [App\..., Polis\...] override pair.
     *
     * @param  class-string|array{0: class-string, 1: class-string}  $concrete
     * @return class-string
     */
    private function resolve(string|array $concrete): string
    {
        if (is_array($concrete)) {
            return BaseServiceProvider::resolveConsumerOrPackage($concrete[0], $concrete[1]);
        }

        return $concrete;
    }

    private function config(): Repository
    {
        return $this->app->make('config');
    }
}
