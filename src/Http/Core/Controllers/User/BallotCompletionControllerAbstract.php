<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\User;

use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Polis\Contracts\Repositories\Vote\BallotCompletionRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests;

/**
 * Class BallotCompletionControllerAbstract
 */
abstract class BallotCompletionControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    private BallotCompletionRepositoryContract $repository;

    /**
     * BallotCompletionControllerAbstract constructor.
     */
    public function __construct(BallotCompletionRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return LengthAwarePaginator
     */
    public function index(Requests\User\BallotCompletion\IndexRequest $request, User $user)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [$user], (int) $request->input('page', 1));
    }
}
