<?php

declare(strict_types=1);

namespace Polis\Plugins;

/**
 * Default, in-memory {@see PluginRegistrar} implementation used by
 * {@see PluginManager}.
 *
 * It does nothing but accumulate a single plugin's declarations into typed
 * arrays. The manager reads those arrays back out (via the public accessors)
 * and performs the actual application against the container, event dispatcher,
 * router, etc. Keeping registration (here) separate from application (manager)
 * is what makes the whole system deterministic and idempotent: the same
 * declarations always produce the same aggregated result.
 *
 * Scoped to one plugin instance — the manager creates a fresh registrar per
 * plugin so the plugin key is known and config can be namespaced correctly.
 */
final class DefaultPluginRegistrar implements PluginRegistrar
{
    /** @var array<class-string, class-string|array{0: class-string, 1: class-string}|callable|\Closure> */
    private array $bindings = [];

    /** @var array<class-string, array<int, class-string>> */
    private array $listeners = [];

    /** @var array<class-string, class-string|array{0: class-string, 1: class-string}> */
    private array $observers = [];

    /** @var array<int, array{path: string, opts: array<string, mixed>}> */
    private array $routes = [];

    /** @var array<int, string> */
    private array $migrations = [];

    /** @var array<string, array<string, mixed>> */
    private array $config = [];

    /** @var array<string, class-string> */
    private array $validators = [];

    /** @var array<class-string, class-string|array{0: class-string, 1: class-string}> */
    private array $policies = [];

    /** @var array<string, array<string, mixed>> */
    private array $entityTypes = [];

    public function __construct(private readonly string $pluginKey) {}

    public function bindings(array $contractToConcrete): static
    {
        foreach ($contractToConcrete as $contract => $concrete) {
            $this->bindings[$contract] = $concrete;
        }

        return $this;
    }

    public function listeners(array $eventToListeners): static
    {
        foreach ($eventToListeners as $event => $listeners) {
            $existing = $this->listeners[$event] ?? [];
            // Preserve order, drop duplicates so re-registration is idempotent.
            $this->listeners[$event] = array_values(array_unique(
                array_merge($existing, (array) $listeners)
            ));
        }

        return $this;
    }

    public function observers(array $modelToObserver): static
    {
        foreach ($modelToObserver as $model => $observer) {
            $this->observers[$model] = $observer;
        }

        return $this;
    }

    public function routes(string $path, array $opts = []): static
    {
        $this->routes[] = ['path' => $path, 'opts' => $opts];

        return $this;
    }

    public function migrations(string $path): static
    {
        if (! in_array($path, $this->migrations, true)) {
            $this->migrations[] = $path;
        }

        return $this;
    }

    public function config(string $key, array $default): static
    {
        $this->config[$key] = $default;

        return $this;
    }

    public function validators(array $ruleToClass): static
    {
        foreach ($ruleToClass as $rule => $class) {
            $this->validators[$rule] = $class;
        }

        return $this;
    }

    public function policies(array $modelToPolicy): static
    {
        foreach ($modelToPolicy as $model => $policy) {
            $this->policies[$model] = $policy;
        }

        return $this;
    }

    public function entityType(string $type, array $definition = []): static
    {
        $this->entityTypes[$type] = $definition;

        return $this;
    }

    public function pluginKey(): string
    {
        return $this->pluginKey;
    }

    /** @return array<class-string, class-string|array{0: class-string, 1: class-string}|callable|\Closure> */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /** @return array<class-string, array<int, class-string>> */
    public function getListeners(): array
    {
        return $this->listeners;
    }

    /** @return array<class-string, class-string|array{0: class-string, 1: class-string}> */
    public function getObservers(): array
    {
        return $this->observers;
    }

    /** @return array<int, array{path: string, opts: array<string, mixed>}> */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /** @return array<int, string> */
    public function getMigrations(): array
    {
        return $this->migrations;
    }

    /** @return array<string, array<string, mixed>> */
    public function getConfig(): array
    {
        return $this->config;
    }

    /** @return array<string, class-string> */
    public function getValidators(): array
    {
        return $this->validators;
    }

    /** @return array<class-string, class-string|array{0: class-string, 1: class-string}> */
    public function getPolicies(): array
    {
        return $this->policies;
    }

    /** @return array<string, array<string, mixed>> */
    public function getEntityTypes(): array
    {
        return $this->entityTypes;
    }
}
