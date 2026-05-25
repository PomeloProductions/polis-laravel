<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use App\Http\Core\Requests;
use App\Models\Statistic\Statistic;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Repositories\Statistic\StatisticRepositoryContract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;

/**
 * Class StatisticControllerAbstract
 */
abstract class StatisticControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * @var StatisticRepositoryContract
     */
    protected $repository;

    /**
     * StatisticControllerAbstract constructor.
     */
    public function __construct(StatisticRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource
     *
     * @return JsonResponse
     */
    public function index(Requests\Statistic\IndexRequest $request)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [], (int) $request->input('page', 1));
    }

    /**
     * Creates a Statistic model
     *
     * @return JsonResponse
     */
    public function store(Requests\Statistic\StoreRequest $request)
    {
        $model = $this->repository->create($request->json()->all());

        return response($model, 201);
    }

    /**
     * View a single Statistic model
     *
     * @return JsonResponse
     */
    public function show(Requests\Statistic\ViewRequest $request, Statistic $statistic)
    {
        return $statistic->load($this->expand($request));
    }

    /**
     * Updates a Statistic model
     *
     * @return JsonResponse
     */
    public function update(Requests\Statistic\UpdateRequest $request, Statistic $statistic)
    {
        return $this->repository->update($statistic, $request->json()->all());
    }

    /**
     * Deletes a Statistic model
     *
     * @return JsonResponse
     */
    public function destroy(Requests\Statistic\DeleteRequest $request, Statistic $statistic)
    {
        $this->repository->delete($statistic);

        return response(null, 204);
    }
}
