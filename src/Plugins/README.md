# Polis backend plugin system

This is the backend half of the Polis plugin system. The **contract lives in
this package** so every consumer app (client-driver, PolisOS, HighScoresCenter,
Card-Collecting, CNH, …) can declaratively load plugins with zero provider
hand-wiring. The frontend half (polis-react) mirrors the identity/metadata
surface (`key` / `name` / `version` / `capabilities`).

## The contract

A plugin implements [`PluginContract`](PluginContract.php):

```php
interface PluginContract
{
    public function key(): string;          // stable id, e.g. "todo"
    public function name(): string;          // display name
    public function version(): string;       // semver, for backend/frontend parity
    public function capabilities(): array;   // advisory capability keys
    public function register(PluginRegistrar $registrar): void;
}
```

Inside `register()` the plugin declares its capabilities against the
[`PluginRegistrar`](PluginRegistrar.php) — never against the container/router
directly, so the [`PluginManager`](PluginManager.php) stays the single,
deterministic point of application:

```php
public function register(PluginRegistrar $registrar): void
{
    $registrar
        ->bindings([
            TodoRepositoryContract::class => [\App\Repositories\TodoRepository::class, \Polis\Repositories\TodoRepository::class],
        ])
        ->listeners([TaskCompletedEvent::class => [NotifyWatchersListener::class]])
        ->observers([Task::class => TaskObserver::class])
        ->routes(__DIR__.'/../routes/todo.php', ['prefix' => 'todo', 'middleware' => 'api-v1'])
        ->migrations(__DIR__.'/../database/migrations')
        ->config('defaults', ['items_per_page' => 25])
        ->validators(['task_owned_by' => TaskOwnedByValidator::class])
        ->policies([Task::class => TaskPolicy::class])
        ->entityType('task', [/* future entity-type registry */]);
}
```

### Capability reference

| Registrar method | Where it is applied | Notes |
|---|---|---|
| `bindings($map)` | `PluginServiceProvider::register()` | contract => concrete. A `[App\…, Polis\…]` pair resolves via `BaseServiceProvider::resolveConsumerOrPackage()` (consumer override wins). Closures bind as-is. |
| `listeners($map)` | `boot()` (dispatcher) + `aggregatedListeners()` | merged, de-duplicated; also mergeable into a consumer `EventServiceProvider::listens()`. |
| `observers($map)` | `boot()` via `Model::observe()` + `aggregatedObservers()` | model/observer resolve via resolveConsumerOrPackage. |
| `routes($path, $opts)` | `boot()` route group | opts: `middleware`, `prefix`, `namespace`, `version`. |
| `migrations($path)` | `boot()` via `loadMigrationsFrom()` | **closes the migration-autoload gap for plugins** — plugin migrations just run on `migrate`. |
| `config($key, $default)` | `boot()` | merged under `config('plugin.<pluginKey>.<key>')`; consumer values win. |
| `validators($map)` | `boot()` via validator factory `extend()` | rule-name => validator class. |
| `policies($map)` | `boot()` via `Gate::policy()` + `aggregatedPolicies()` | model/policy resolve via resolveConsumerOrPackage. |
| `entityType($type, $def)` | recorded only — **STUB** | future entity-type registry (0.3 User→Entity roadmap). Retrieve via `PluginManager::entityTypes()`. |

## Discovery & activation (zero consumer wiring)

1. A consumer lists plugin class strings in `config('plugins.enabled')`
   (default `config/plugins.php` ships `['enabled' => []]`).
2. [`PluginServiceProvider`](PluginServiceProvider.php) is **auto-registered**
   via `composer.json` → `extra.laravel.providers`, so requiring the package
   activates it.
3. The provider boots the `PluginManager`, which instantiates each enabled
   plugin, calls `register()`, aggregates the declarations, and applies them.

A consumer with no `config/plugins.php` or an empty `enabled` array is a
**complete no-op** — existing apps are unaffected.

## Composition with the Base\* providers

The manager is the *only* thing that touches the container/router/dispatcher,
so nothing is double-registered. The `Base*` providers are untouched and keep
working. If a consumer prefers plugin contributions to flow through its own
provider mapping, the manager exposes `aggregatedListeners()`,
`aggregatedObservers()`, and `aggregatedPolicies()` as one-line merge hooks —
but that is optional.

## Determinism & idempotency

Plugins are processed in `config('plugins.enabled')` order and de-duplicated by
`key()` (first wins). `discover()` caches on the instance; re-running
registration produces the same aggregated result. Migration paths and listener
lists are de-duplicated.
