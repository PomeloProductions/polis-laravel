<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\User;

use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Repositories\Messaging\ThreadRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests;

/**
 * Class ThreadControllerAbstract
 */
abstract class ThreadControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * @var ThreadRepositoryContract
     */
    private $repository;

    /**
     * ThreadController constructor.
     */
    public function __construct(ThreadRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return LengthAwarePaginator
     */
    public function index(Requests\User\Thread\IndexRequest $request, User $user)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [$user], (int) $request->input('page', 1));
    }

    public function store(Requests\User\Thread\StoreRequest $request, User $user): JsonResponse
    {
        $data = $request->json()->all();
        $data['users'][] = $user->id;

        return new JsonResponse($this->repository->create($data), 201);
    }
}
