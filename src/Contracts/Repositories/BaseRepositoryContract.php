<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Polis\Models\BaseModelAbstract;

/**
 * Interface BaseRepositoryContract
 */
interface BaseRepositoryContract
{
    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  int|string  $id
     * @param  array  $with  relationships to load eagerly
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int|string $id, array $with = []): BaseModelAbstract;

    /**
     * Find all
     *
     * @param  array  $orderBy  This needs to be an array of of field names with each value indicating the needed order
     * @param  int|null  $limit  pass null to get all
     * @param  array  $belongsToArray  array of models this should belong to
     * @param  int  $page
     */
    public function findAll(array $filters = [], array $searches = [], array $orderBy = [], array $with = [], $limit = 10, array $belongsToArray = [], int $page = 1): LengthAwarePaginator|Collection;

    /**
     * Save a new instance of this model, and then return the instance
     *
     * @param  BaseModelAbstract|null  $relatedModel  if there is a relationship to build
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = []): BaseModelAbstract;

    /**
     * Update the model
     */
    public function update(BaseModelAbstract $model, array $data, array $forcedValues = []): BaseModelAbstract;

    /**
     * Delete this single model
     */
    public function delete(BaseModelAbstract $model): bool;
}
