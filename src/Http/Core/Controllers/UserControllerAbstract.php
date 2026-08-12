<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Contracts\Services\StripeCustomerServiceContract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Controllers\Traits\HasViewRequests;
use Polis\Http\Core\Requests;
use Polis\Models\BaseModelAbstract;

/**
 * Class UserControllerAbstract
 */
abstract class UserControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests, HasViewRequests;

    /**
     * @var UserRepositoryContract
     */
    protected $repository;

    /**
     * @var StripeCustomerServiceContract
     */
    protected $stripeCustomerService;

    /**
     * UsersController constructor.
     */
    public function __construct(UserRepositoryContract $repository,
        StripeCustomerServiceContract $stripeCustomerService)
    {
        $this->repository = $repository;
        $this->stripeCustomerService = $stripeCustomerService;
    }

    /**
     * Display a listing of users (Super Admin only)
     */
    public function index(Requests\User\IndexRequest $request): LengthAwarePaginator
    {
        return $this->repository->findAll(
            $this->filter($request),
            $this->search($request),
            $this->order($request),
            $this->expand($request),
            $this->limit($request),
            [],
            (int) $request->input('page', 1)
        );
    }

    /**
     * Store a newly created user (Super Admin only)
     */
    public function store(Requests\User\StoreRequest $request): JsonResponse
    {
        $data = $request->json()->all();

        /** @var User $model */
        $model = $this->repository->create($data);

        return new JsonResponse($model, 201);
    }

    /**
     * Display the specified resource.
     *
     * @SWG\Get(
     *     path="/users/{id}",
     *     summary="Get a single user",
     *     tags={"Users"},
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
     *          @SWG\Schema(ref="#/definitions/User"),
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
     * @return User
     */
    public function show(Requests\User\ViewRequest $request, User $user)
    {
        return $user->load($this->expand($request));
    }

    /**
     * Update the specified resource in storage.
     *
     * @SWG\Patch(
     *     path="/users/{id}",
     *     summary="Updates a single user",
     *     tags={"Users"},
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
     *          @SWG\Schema(ref="#/definitions/User"),
     *          description="The model updates to make"
     *     ),
     *
     *     @SWG\Response(
     *          response=200,
     *          description="Successful update",
     *
     *          @SWG\Schema(ref="#/definitions/User"),
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
     * @return User|BaseModelAbstract
     */
    public function update(Requests\User\UpdateRequest $request, User $user)
    {
        return $this->repository->update($user, $request->json()->all());
    }

    /**
     * Display the specified resource's self
     *
     * @SWG\Get(
     *     path="/users/me",
     *     summary="Show currently logged in user info",
     *     tags={"Users"},
     *
     *     @SWG\Parameter(ref="#/parameters/AuthorizationHeader"),
     *
     *     @SWG\Response(
     *          response=200,
     *          description="Returns a single model",
     *
     *          @SWG\Schema(ref="#/definitions/User"),
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
     * @return JsonResponse
     */
    public function me(Requests\User\MeRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();

        return new JsonResponse($user->load($this->expand($request)));
    }

    /**
     * Remove the specified user (Super Admin only)
     */
    public function destroy(Requests\User\DeleteRequest $request, User $user): JsonResponse
    {
        $this->repository->delete($user);

        return new JsonResponse(null, 204);
    }
}
