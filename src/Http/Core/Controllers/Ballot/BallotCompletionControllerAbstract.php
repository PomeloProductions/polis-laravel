<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Ballot;

use App\Models\Vote\Ballot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Polis\Contracts\Repositories\Vote\BallotCompletionRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Requests;

/**
 * Class BallotCompletionControllerAbstract
 */
abstract class BallotCompletionControllerAbstract extends BaseControllerAbstract
{
    private BallotCompletionRepositoryContract $repository;

    /**
     * BallotCompletionControllerAbstract constructor.
     */
    public function __construct(BallotCompletionRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return JsonResponse
     */
    public function store(Requests\Ballot\BallotCompletion\StoreRequest $request, Ballot $ballot)
    {
        $data = $request->json()->all();

        $data['user_id'] = Auth::user()->id;

        $model = $this->repository->create($data, $ballot);
        $model->load('votes');

        return new JsonResponse($model, 201);
    }
}
