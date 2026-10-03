<?php

declare(strict_types=1);

namespace Polis\Plugins;

use Polis\Providers\BaseEventServiceProvider;
use Polis\Providers\BaseServiceProvider;

/**
 * The capability-registration surface a plugin uses inside
 * {@see PluginContract::register()}.
 *
 * Every method is a declaration, not an immediate side effect: the registrar
 * accumulates the plugin's contributions, and {@see PluginManager} applies the
 * aggregated result once (bindings in `register()`, listeners into `listens()`,
 * observers + validators + policies in `boot()`, routes in `map()`, migrations
 * via `loadMigrationsFrom()`, and config via `mergeConfigFrom`). This keeps the
 * manager the single, deterministic point of application and lets the same
 * override philosophy that governs the Base* providers
 * ({@see BaseServiceProvider::resolveConsumerOrPackage()})
 * apply to plugin-contributed bindings too.
 *
 * Methods are fluent (return `$this`) so a plugin can chain declarations.
 */
interface PluginRegistrar
{
    /**
     * Declare container bindings as a contract => concrete map.
     *
     * The concrete is resolved through
     * {@see BaseServiceProvider::resolveConsumerOrPackage()}
     * semantics when it is given as an [App\..., Polis\...] pair (consumer
     * override preferred, package concrete fallback). A plain string concrete
     * binds as-is. This means a consumer can still override a plugin binding
     * by shipping an `App\...` class, exactly as with the package's own
     * bindings.
     *
     * @param  array<class-string, class-string|array{0: class-string, 1: class-string}|callable|\Closure>  $contractToConcrete
     */
    public function bindings(array $contractToConcrete): static;

    /**
     * Declare event listeners as an event => [listeners] map. These are merged
     * into the aggregated listener mapping the event provider exposes via
     * {@see BaseEventServiceProvider::listens()}.
     *
     * @param  array<class-string, array<int, class-string>>  $eventToListeners
     */
    public function listeners(array $eventToListeners): static;

    /**
     * Declare model observers as a model => observer map. Applied in the event
     * provider's boot via `Model::observe(Observer::class)`. The model key is
     * resolved with resolveConsumerOrPackage semantics when given as a pair.
     *
     * @param  array<class-string, class-string|array{0: class-string, 1: class-string}>  $modelToObserver
     */
    public function observers(array $modelToObserver): static;

    /**
     * Declare a plugin route file (or group) to load. Options may include:
     *  - 'middleware' => string|array (default 'api')
     *  - 'prefix'     => string
     *  - 'namespace'  => string (controller namespace for the group)
     *  - 'version'    => string (informational; e.g. 'v1')
     *
     * Applied in the route provider's `map()` via a `Route::group`.
     *
     * @param  array<string, mixed>  $opts
     */
    public function routes(string $path, array $opts = []): static;

    /**
     * Declare an absolute directory of plugin migrations. Unlike the package's
     * own `database/migrations` (which are deliberately NOT auto-loaded), every
     * registered plugin migration directory is wired through Laravel's
     * `loadMigrationsFrom()` so plugin migrations just run on `migrate`.
     */
    public function migrations(string $path): static;

    /**
     * Declare namespaced default config for this plugin. Merged under
     * `config('plugin.<pluginKey>.<key>')` so a consumer can override any value
     * from their own `config/plugin.php` without colliding with core
     * `config/polis.php`. Only fills keys the consumer has not already set
     * (mergeConfigFrom semantics).
     *
     * @param  array<string, mixed>  $default
     */
    public function config(string $key, array $default): static;

    /**
     * Declare custom validators as a rule-name => validator-class map, applied
     * via the validator factory's `extend()` in the validator provider's boot.
     *
     * @param  array<string, class-string>  $ruleToClass
     */
    public function validators(array $ruleToClass): static;

    /**
     * Declare authorization policies as a model => policy map. Merged into the
     * auth provider's policy registry. Model/policy may each be a
     * resolveConsumerOrPackage pair.
     *
     * @param  array<class-string, class-string|array{0: class-string, 1: class-string}>  $modelToPolicy
     */
    public function policies(array $modelToPolicy): static;

    /**
     * Declare an entity type this plugin introduces (future entity-type
     * registry; see the 0.3 roadmap generalizing User -> Entity). Accepted and
     * recorded now so plugin authors can target a stable signature, but the
     * registry that consumes these is not yet wired — this is a documented
     * STUB. The recorded declarations are retrievable via
     * {@see PluginManager::entityTypes()} for the follow-up that builds the
     * registry.
     *
     * @param  array<string, mixed>  $definition
     */
    public function entityType(string $type, array $definition = []): static;
}
