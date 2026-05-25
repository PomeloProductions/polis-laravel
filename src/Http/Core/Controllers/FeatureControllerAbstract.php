<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use App\Http\Core\Requests;
use App\Models\Feature;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Polis\Contracts\Repositories\FeatureRepositoryContract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;

/**
 * Class FeatureControllerAbstract
 */
abstract class FeatureControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    protected FeatureRepositoryContract $repository;

    /**
     * FeatureControllerAbstract constructor.
     */
    public function __construct(FeatureRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return LengthAwarePaginator
     */
    public function index(Requests\Feature\IndexRequest $request)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [], (int) $request->input('page', 1));
    }

    /**
     * Display the specified resource.
     *
     * @return Feature
     */
    public function show(Requests\Feature\ViewRequest $request, Feature $model)
    {
        return $model->load($this->expand($request));
    }
}
