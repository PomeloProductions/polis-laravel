<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\MembershipPlan;

use App\Models\Subscription\MembershipPlan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRateRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests;

/**
 * Class MembershipPlanRateControllerAbstract
 */
abstract class MembershipPlanRateControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    protected MembershipPlanRateRepositoryContract $repository;

    /**
     * MembershipPlanRateController constructor.
     */
    public function __construct(MembershipPlanRateRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return LengthAwarePaginator|Collection
     */
    public function index(Requests\MembershipPlan\MembershipPlanRate\IndexRequest $request, MembershipPlan $membershipPlan)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [$membershipPlan], (int) $request->input('page', 1));
    }
}
