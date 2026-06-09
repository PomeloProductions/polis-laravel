<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

use Illuminate\Database\Eloquent\Collection;
use Polis\Models\BaseModelAbstract;

/**
 * Interface ModelCacheServiceContract
 *
 * A driver-agnostic cache facade for Eloquent models. Backed by Laravel's
 * cache repository abstraction, so consumers running Redis get Redis,
 * consumers running an array store for tests get an array store, etc.
 *
 * Key strategy mirrors the Lingwave original: a single collection-level key
 * (e.g. `model:App\Models\Widget`) holding the full Collection of models.
 * `remember()` is provided as the canonical "hit-or-miss" entry point;
 * `forget()` invalidates the key. Lingwave's `getAllModels` / `findById` /
 * `cacheModel` / `removeModel` methods are preserved for API compatibility
 * with the originating service.
 */
interface ModelCacheServiceContract
{
    /**
     * Returns the cached collection if present, otherwise invokes the
     * loader, stores the result, and returns it.
     *
     * @param  callable():Collection  $loader
     */
    public function remember(callable $loader): Collection;

    /**
     * Invalidates the cached collection for this service entirely.
     */
    public function forget(): void;

    /**
     * Returns all models for this cache. Misses load through the repository
     * provided at construction time and warm the cache.
     *
     * @return Collection|BaseModelAbstract[]
     */
    public function getAllModels(): Collection;

    /**
     * Finds a model in the cached collection by id.
     */
    public function findById(int $id): ?BaseModelAbstract;

    /**
     * Inserts or replaces the model within the cached collection.
     */
    public function cacheModel(BaseModelAbstract $model): void;

    /**
     * Removes the model from the cached collection.
     */
    public function removeModel(BaseModelAbstract $model): void;
}
