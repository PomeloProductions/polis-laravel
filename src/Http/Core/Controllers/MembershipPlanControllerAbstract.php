<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use App\Http\Core\Requests;
use App\Models\Subscription\MembershipPlan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRepositoryContract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Models\BaseModelAbstract;

/**
 * Class MembershipPlanControllerAbstract
 */
abstract class MembershipPlanControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * @var MembershipPlanRepositoryContract
     */
    protected $repository;

    /**
     * BundleController constructor.
     */
    public function __construct(MembershipPlanRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @SWG\Get(
     *     path="/membership-plans",
     *     summary="Get all membership plans",
     *     tags={"MembershipPlans"},
     *
     *     @SWG\Parameter(ref="#/parameters/AuthorizationHeader"),
     *     @SWG\Parameter(ref="#/parameters/PaginationPage"),
     *     @SWG\Parameter(ref="#/parameters/PaginationLimit"),
     *     @SWG\Parameter(ref="#/parameters/SearchParameter"),
     *     @SWG\Parameter(ref="#/parameters/FilterParameter"),
     *     @SWG\Parameter(ref="#/parameters/ExpandParameter"),
     *
     *     @SWG\Response(
     *          response=200,
     *          description="Returns a collection of the model",
     *
     *          @SWG\Schema(ref="#/definitions/PagedMembershipPlans"),
     *
     *          @SWG\Header(
     *              header="X-RateLimit-Limit",
     *              description="The number of allowed requests in the period",
     *              type="integer"
     *          ),
     *          @SWG\Header(
     *              header="X-RateLimit-Remaining",
     *              description="The number of remaining requests in the period",
     *              type="integer"
     *          )
     *      ),
     *
     *     @SWG\Response(
     *          response=400,
     *          ref="#/responses/Standard400BadRequestResponse"
     *      ),
     *     @SWG\Response(
     *          response=401,
     *          ref="#/responses/Standard401UnauthorizedResponse"
     *      ),
     *     @SWG\Response(
     *          response=404,
     *          ref="#/responses/Standard404PagingRequestTooLarge"
     *      ),
     *     @SWG\Response(
     *          response="default",
     *          ref="#/responses/Standard500ErrorResponse"
     *      ),
     * )
     *
     * @SWG\Definition(
     *     definition="PagedBundles",
     *     allOf={
     *
     *          @SWG\Schema(ref="#/definitions/GiftPackBundles"),
     *          @SWG\Schema(ref="#/definitions/Paging")
     *     }
     * )
     *
     * @SWG\Definition(
     *     definition="MembershipPlans",
     *
     *     @SWG\Property(
     *          property="data",
     *          type="array",
     *          minItems=0,
     *          maxItems=100,
     *          uniqueItems=true,
     *
     *          @SWG\Items(ref="#/definitions/MembershipPlan")
     *     )
     * )
     *
     * @return LengthAwarePaginator
     */
    public function index(Requests\MembershipPlan\IndexRequest $request)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [], (int) $request->input('page', 1));
    }

    /**
     * Display the specified resource.
     *
     * @SWG\Get(
     *     path="/membership-plans/{id}",
     *     summary="Get a single membership plan",
     *     tags={"MembershipPlans"},
     *
     *     @SWG\Parameter(ref="#/parameters/AuthorizationHeader"),
     *     @SWG\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          type="integer",
     *          format="int32",
     *          description="The ID of the model"
     *     ),
     *
     *     @SWG\Response(
     *          response=200,
     *          description="Returns a single model",
     *
     *          @SWG\Schema(ref="#/definitions/MembershipPlan"),
     *
     *          @SWG\Header(
     *              header="X-RateLimit-Limit",
     *              description="The number of allowed requests in the period",
     *              type="integer"
     *          ),
     *          @SWG\Header(
     *              header="X-RateLimit-Remaining",
     *              description="The number of remaining requests in the period",
     *              type="integer"
     *          )
     *      ),
     *
     *     @SWG\Response(
     *          response=400,
     *          ref="#/responses/Standard400BadRequestResponse"
     *      ),
     *     @SWG\Response(
     *          response=401,
     *          ref="#/responses/Standard401UnauthorizedResponse"
     *      ),
     *     @SWG\Response(
     *          response=404,
     *          ref="#/responses/Standard404ItemNotFoundResponse"
     *      ),
     *     @SWG\Response(
     *          response="default",
     *          ref="#/responses/Standard500ErrorResponse"
     *      ),
     * )
     *
     * @return MembershipPlan
     */
    public function show(Requests\MembershipPlan\ViewRequest $request, MembershipPlan $membershipPlan)
    {
        return $membershipPlan->load($this->expand($request));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @SWG\Post(
     *     path="/bundles",
     *     summary="Create a new membership plan",
     *     tags={"MembershipPlans"},
     *
     *     @SWG\Parameter(ref="#/parameters/AuthorizationHeader"),
     *     @SWG\Parameter(
     *          name="keyword-variation",
     *          in="body",
     *          required=true,
     *
     *          @SWG\Schema(ref="#/definitions/MembershipPlan"),
     *          description="The model to create"
     *     ),
     *
     *     @SWG\Response(
     *          response=201,
     *          description="Model created successfully",
     *
     *          @SWG\Schema(ref="#/definitions/MembershipPlan"),
     *
     *          @SWG\Header(
     *              header="Location",
     *              description="The URL to retrieve the newly created item",
     *              type="string",
     *              format="url"
     *          ),
     *          @SWG\Header(
     *              header="X-RateLimit-Limit",
     *              description="The number of allowed requests in the period",
     *              type="integer"
     *          ),
     *          @SWG\Header(
     *              header="X-RateLimit-Remaining",
     *              description="The number of remaining requests in the period",
     *              type="integer"
     *          )
     *      ),
     *
     *     @SWG\Response(
     *          response=400,
     *          ref="#/responses/Standard400BadRequestResponse"
     *      ),
     *     @SWG\Response(
     *          response=401,
     *          ref="#/responses/Standard401UnauthorizedResponse"
     *      ),
     *     @SWG\Response(
     *          response="default",
     *          ref="#/responses/Standard500ErrorResponse"
     *      ),
     * )
     *
     * @return MembershipPlan
     */
    public function store(Requests\MembershipPlan\StoreRequest $request)
    {
        $model = $this->repository->create($request->json()->all());

        return response($model, 201)->header('Location', route('v1.membership-plans.show', ['membership_plan' => $model]));
    }

    /**
     * Update the specified resource in storage.
     *
     * @SWG\Patch(
     *     path="/membership-plans/{id}",
     *     summary="Updates a single bundle",
     *     tags={"GiftPackBundles"},
     *
     *     @SWG\Parameter(ref="#/parameters/AuthorizationHeader"),
     *     @SWG\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          type="integer",
     *          format="int32",
     *          description="The ID of the model"
     *     ),
     *     @SWG\Parameter(
     *          name="model",
     *          in="body",
     *          required=true,
     *
     *          @SWG\Schema(ref="#/definitions/MembershipPlan"),
     *          description="The model updates to make"
     *     ),
     *
     *     @SWG\Response(
     *          response=200,
     *          description="Successful update",
     *
     *          @SWG\Schema(ref="#/definitions/MembershipPlan"),
     *
     *          @SWG\Header(
     *              header="X-RateLimit-Limit",
     *              description="The number of allowed requests in the period",
     *              type="integer"
     *          ),
     *          @SWG\Header(
     *              header="X-RateLimit-Remaining",
     *              description="The number of remaining requests in the period",
     *              type="integer"
     *          )
     *      ),
     *
     *     @SWG\Response(
     *          response=400,
     *          ref="#/responses/Standard400BadRequestResponse"
     *      ),
     *     @SWG\Response(
     *          response=401,
     *          ref="#/responses/Standard401UnauthorizedResponse"
     *      ),
     *     @SWG\Response(
     *          response=404,
     *          ref="#/responses/Standard404ItemNotFoundResponse"
     *      ),
     *     @SWG\Response(
     *          response="default",
     *          ref="#/responses/Standard500ErrorResponse"
     *      ),
     * )
     *
     * @return BaseModelAbstract
     */
    public function update(Requests\MembershipPlan\UpdateRequest $request, MembershipPlan $membershipPlan)
    {
        return $this->repository->update($membershipPlan, $request->json()->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @SWG\Delete(
     *     path="/membership-plans/{id}",
     *     summary="Delete a single membership plan",
     *     tags={"GiftPackBundles"},
     *
     *     @SWG\Parameter(ref="#/parameters/AuthorizationHeader"),
     *     @SWG\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          type="integer",
     *          format="int32",
     *          description="The ID of the model"
     *     ),
     *
     *     @SWG\Response(
     *          response=204,
     *          description="Successful deletion",
     *
     *          @SWG\Header(
     *              header="X-RateLimit-Limit",
     *              description="The number of allowed requests in the period",
     *              type="integer"
     *          ),
     *          @SWG\Header(
     *              header="X-RateLimit-Remaining",
     *              description="The number of remaining requests in the period",
     *              type="integer"
     *          )
     *      ),
     *
     *     @SWG\Response(
     *          response=400,
     *          ref="#/responses/Standard400BadRequestResponse"
     *      ),
     *     @SWG\Response(
     *          response=401,
     *          ref="#/responses/Standard401UnauthorizedResponse"
     *      ),
     *     @SWG\Response(
     *          response=404,
     *          ref="#/responses/Standard404ItemNotFoundResponse"
     *      ),
     *     @SWG\Response(
     *          response="default",
     *          ref="#/responses/Standard500ErrorResponse"
     *      ),
     * )
     *
     * @return null
     */
    public function destroy(Requests\MembershipPlan\DeleteRequest $request, MembershipPlan $model)
    {
        $this->repository->delete($model);

        return response(null, 204);
    }
}
