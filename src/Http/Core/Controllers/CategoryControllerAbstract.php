<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use App\Http\Core\Requests;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Polis\Contracts\Repositories\CategoryRepositoryContract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Models\BaseModelAbstract;

/**
 * Class MemberCardControllerAbstract
 */
abstract class CategoryControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    protected CategoryRepositoryContract $repository;

    /**
     * MemberCardController constructor.
     */
    public function __construct(CategoryRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return LengthAwarePaginator
     */
    public function index(Requests\Category\IndexRequest $request)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [], (int) $request->input('page', 1));
    }

    /**
     * Display the specified resource.
     *
     * @return Category
     */
    public function show(Requests\Category\ViewRequest $request, Category $model)
    {
        return $model->load($this->expand($request));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Category
     */
    public function store(Requests\Category\StoreRequest $request)
    {
        $model = $this->repository->create($request->json()->all());

        return response($model, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return BaseModelAbstract
     */
    public function update(Requests\Category\UpdateRequest $request, Category $membershipPlan)
    {
        return $this->repository->update($membershipPlan, $request->json()->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return null
     */
    public function destroy(Requests\Category\DeleteRequest $request, Category $model)
    {
        $this->repository->delete($model);

        return response(null, 204);
    }
}
