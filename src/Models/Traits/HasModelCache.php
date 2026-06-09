<?php

declare(strict_types=1);

namespace Polis\Models\Traits;

use Polis\Contracts\Services\ModelCacheServiceContract;
use Polis\Observers\BaseModelCacheObserver;

/**
 * Trait HasModelCache
 *
 * Opt-in for `Polis\Models\BaseModelAbstract` subclasses that want their
 * saves/updates/deletes mirrored into a cached collection via
 * `BaseModelCacheObserver`. Consumers either:
 *
 *   1. Override `modelCacheObserver()` to return the FQCN of an observer
 *      subclass (recommended — keeps DI consistent across models), or
 *   2. Override `modelCacheService()` to return the cache service contract
 *      class to inject into the default observer.
 *
 * Example:
 *
 *     class Widget extends BaseModelAbstract
 *     {
 *         use HasModelCache;
 *
 *         protected static function modelCacheService(): string
 *         {
 *             return WidgetCacheServiceContract::class;
 *         }
 *     }
 *
 * The observer is wired via Eloquent's standard `observe()` API inside
 * `bootHasModelCache()`, which Eloquent invokes once per model class boot.
 * Consumers that need to disable caching globally (e.g. inside a slow
 * migration) can set `polis.model_cache.enabled` to false in config; the
 * observer registration short-circuits when disabled.
 */
trait HasModelCache
{
    /**
     * Eloquent calls this once per model class boot — see
     * `Illuminate\Database\Eloquent\Concerns\HasUuids` for the upstream
     * convention. The method name MUST stay `boot<TraitName>` for Eloquent
     * to find it via reflection.
     */
    public static function bootHasModelCache(): void
    {
        if (function_exists('config') && config('polis.model_cache.enabled', true) === false) {
            return;
        }

        static::observe(static::modelCacheObserver());
    }

    /**
     * FQCN of the observer to register against this model. Override to
     * supply a model-specific observer subclass; defaults to the generic
     * `BaseModelCacheObserver`, which Laravel will resolve from the
     * container so the cache service contract gets injected.
     */
    protected static function modelCacheObserver(): string
    {
        return BaseModelCacheObserver::class;
    }

    /**
     * FQCN of the cache service contract the default observer should
     * resolve. Override to point at a model-specific contract; the default
     * is the generic `ModelCacheServiceContract`.
     */
    protected static function modelCacheService(): string
    {
        return ModelCacheServiceContract::class;
    }
}
