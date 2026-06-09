<?php

declare(strict_types=1);

namespace Polis\Observers;

use Polis\Contracts\Services\ModelCacheServiceContract;
use Polis\Models\BaseModelAbstract;

/**
 * Class BaseModelCacheObserver
 *
 * Ported from Lingwave. Each Eloquent model that opts into caching attaches
 * (a subclass of) this observer; the observer mirrors save/delete events to
 * the matching cache service so the cached collection stays consistent with
 * the database.
 *
 * Concrete subclasses simply type-hint a more specific contract in their
 * constructor and delegate to the parent.
 */
class BaseModelCacheObserver
{
    public function __construct(
        protected readonly ModelCacheServiceContract $cacheService,
    ) {}

    /**
     * Handles the model "created" event.
     */
    public function created(BaseModelAbstract $model): void
    {
        $this->cacheService->cacheModel($model);
    }

    /**
     * Handles the model "updated" event.
     */
    public function updated(BaseModelAbstract $model): void
    {
        $this->cacheService->cacheModel($model);
    }

    /**
     * Handles the model "deleting" event. Lingwave uses `deleting` rather
     * than `deleted` so the row can still be identified by id before the
     * soft-delete column is written.
     */
    public function deleting(BaseModelAbstract $model): void
    {
        $this->cacheService->removeModel($model);
    }
}
