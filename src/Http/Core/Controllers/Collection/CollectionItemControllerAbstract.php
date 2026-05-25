<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Collection;

use App\Http\Core\Requests;
use App\Models\Collection\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Repositories\Collection\CollectionItemRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;

/**
 * Class ThreadControllerAbstract
 */
abstract class CollectionItemControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * ThreadController constructor.
     */
    public function __construct(protected CollectionItemRepositoryContract $repository) {}

    /**
     * @return LengthAwarePaginator
     */
    public function index(Requests\Collection\CollectionItem\IndexRequest $request, Collection $collection)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [$collection], (int) $request->input('page', 1));
    }

    public function store(Requests\Collection\CollectionItem\StoreRequest $request, Collection $collection): JsonResponse
    {
        $data = $request->json()->all();

        return new JsonResponse($this->repository->create($data, $collection), 201);
    }
}
