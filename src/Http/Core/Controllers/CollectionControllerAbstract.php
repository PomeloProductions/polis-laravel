<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use App\Models\Category;
use App\Models\Collection\Collection;
use Polis\Contracts\Repositories\Collection\CollectionRepositoryContract;
use Polis\Http\Core\Controllers\Traits\HasViewRequests;
use Polis\Http\Core\Requests;
use Polis\Models\BaseModelAbstract;

abstract class CollectionControllerAbstract
{
    use HasViewRequests;

    public function __construct(protected CollectionRepositoryContract $repository) {}

    /**
     * Display the specified resource.
     *
     * @return Category
     */
    public function show(Requests\Collection\ViewRequest $request, Collection $model)
    {
        return $model->load($this->expand($request));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return BaseModelAbstract
     */
    public function update(Requests\Collection\UpdateRequest $request, Collection $model)
    {
        return $this->repository->update($model, $request->json()->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return null
     */
    public function destroy(Requests\Collection\DeleteRequest $request, Collection $model)
    {
        $this->repository->delete($model);

        return response(null, 204);
    }
}
