<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use App\Models\Vote\Ballot;
use Polis\Contracts\Repositories\Vote\BallotRepositoryContract;
use Polis\Http\Core\Controllers\Traits\HasViewRequests;
use Polis\Http\Core\Requests;

/**
 * Class BallotControllerAbstract
 */
abstract class BallotControllerAbstract extends BaseControllerAbstract
{
    use HasViewRequests;

    protected BallotRepositoryContract $repository;

    /**
     * BallotControllerAbstract constructor.
     */
    public function __construct(BallotRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return Ballot
     */
    public function show(Requests\Ballot\ViewRequest $request, Ballot $ballot)
    {
        return $ballot->load($this->expand($request));
    }
}
