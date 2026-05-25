<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use App\Http\Core\Requests;
use App\Models\Category;
use App\Models\Collection\CollectionItem;
use Polis\Contracts\Repositories\Collection\CollectionItemRepositoryContract;
use Polis\Http\Core\Controllers\Traits\HasViewRequests;

abstract class CollectionItemControllerAbstract
{
    use HasViewRequests;

    public function __construct(protected CollectionItemRepositoryContract $repository) {}

    /**
     * Display the specified resource.
     *
     * @return Category
     */
    public function show(Requests\CollectionItem\ViewRequest $request, CollectionItem $model)
    {
        return $model->load($this->expand($request));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return null
     */
    public function destroy(Requests\CollectionItem\DeleteRequest $request, CollectionItem $model)
    {
        $this->repository->delete($model);

        return response(null, 204);
    }
}
