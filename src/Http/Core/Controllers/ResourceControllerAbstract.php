<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Polis\Contracts\Repositories\ResourceRepositoryContract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests;

/**
 * Class ResourceControllerAbstract
 */
abstract class ResourceControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * @var ResourceRepositoryContract
     */
    private $repository;

    /**
     * ResourcesController constructor.
     */
    public function __construct(ResourceRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return LengthAwarePaginator
     */
    public function index(Requests\Resource\IndexRequest $request)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [], (int) $request->input('page', 1));
    }
}
