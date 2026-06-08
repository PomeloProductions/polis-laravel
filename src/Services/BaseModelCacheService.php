<?php

declare(strict_types=1);

namespace Polis\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;
use Polis\Contracts\Repositories\BaseRepositoryContract;
use Polis\Contracts\Services\ModelCacheServiceContract;
use Polis\Models\BaseModelAbstract;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class BaseModelCacheService
 *
 * Ported from Lingwave's `App\Services\Cache\BaseModelCacheService`. The
 * Lingwave version assumed Redis specifically; this package version uses
 * Laravel's `Illuminate\Contracts\Cache\Repository` so the underlying driver
 * is whatever the consumer has configured (Redis, Memcached, array, etc.).
 */
class BaseModelCacheService implements ModelCacheServiceContract
{
    public function __construct(
        private readonly string $key,
        private readonly Repository $cache,
        private readonly BaseRepositoryContract $repository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function remember(callable $loader): Collection
    {
        try {
            if ($this->cache->has($this->key)) {
                return $this->cache->get($this->key);
            }
        } catch (InvalidArgumentException) {
            // Fall through to a fresh load.
        }

        $models = $loader();

        $this->storeModels($this->filterLoadedModels($models));

        return $models;
    }

    /**
     * {@inheritDoc}
     */
    public function forget(): void
    {
        $this->cache->forget($this->key);
    }

    /**
     * {@inheritDoc}
     *
     * @return Collection|BaseModelAbstract[]
     */
    public function getAllModels(): Collection
    {
        return $this->remember(fn (): Collection => $this->repository->findAll([], [], [], [], null));
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?BaseModelAbstract
    {
        return $this->getAllModels()
            ->first(fn (BaseModelAbstract $model) => $model->id === $id);
    }

    /**
     * {@inheritDoc}
     */
    public function cacheModel(BaseModelAbstract $model): void
    {
        $models = $this->getAllModels();

        $replaced = false;

        $updated = $models->map(function (BaseModelAbstract $existing) use ($model, &$replaced) {
            if ($existing->id === $model->id) {
                $replaced = true;

                return $model;
            }

            return $existing;
        });

        if (! $replaced) {
            $updated->push($model);
        }

        $this->storeModels($updated);
    }

    /**
     * {@inheritDoc}
     */
    public function removeModel(BaseModelAbstract $model): void
    {
        $updated = $this->getAllModels()
            ->reject(fn (BaseModelAbstract $existing) => $existing->id === $model->id);

        $this->storeModels($updated);
    }

    /**
     * Hook for subclasses that want to drop a subset of models before
     * caching (e.g. inactive rows).
     */
    protected function filterLoadedModels(Collection $models): Collection
    {
        return $models;
    }

    /**
     * Persists the working collection into the underlying cache repository.
     */
    private function storeModels(Collection $models): void
    {
        $this->cache->put($this->key, $models);
    }
}
