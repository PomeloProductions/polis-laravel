<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Entity;

use App\Models\Subscription\Subscription;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Contracts\Services\EntitySubscriptionCreationServiceContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests;
use Polis\Models\BaseModelAbstract;

/**
 * Class SubscriptionControllerAbstract
 */
abstract class SubscriptionControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * @var SubscriptionRepositoryContract
     */
    private $repository;

    private EntitySubscriptionCreationServiceContract $entitySubscriptionCreationService;

    /**
     * SubscriptionController constructor.
     */
    public function __construct(SubscriptionRepositoryContract $repository,
        EntitySubscriptionCreationServiceContract $entitySubscriptionCreationService)
    {
        $this->repository = $repository;
        $this->entitySubscriptionCreationService = $entitySubscriptionCreationService;
    }

    /**
     * Gets all assets for a user
     *
     * @return LengthAwarePaginator
     */
    public function index(Requests\Entity\Subscription\IndexRequest $request, IsAnEntityContract $entity)
    {
        $filter = $this->filter($request);

        $filter[] = [
            'subscriber_id',
            '=',
            $entity->id,
        ];
        $filter[] = [
            'subscriber_type',
            '=',
            $entity->morphRelationName(),
        ];

        return $this->repository->findAll($filter, $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [], (int) $request->input('page', 1));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @SWG\Post(
     *     path="/users/{user_id}/subscriptions",
     *     summary="Create a new Subscription model",
     *     tags={"Users","Subscriptions"},
     *
     *     @SWG\Parameter(ref="#/parameters/AuthorizationHeader"),
     *     @SWG\Parameter(
     *          name="model",
     *          in="body",
     *          required=true,
     *
     *          @SWG\Schema(ref="#/definitions/Subscription"),
     *          description="The model to create"
     *     ),
     *
     *     @SWG\Parameter(
     *          name="user_id",
     *          in="path",
     *          required=true,
     *          type="integer",
     *          format="int32",
     *          description="The ID of the user model"
     *     ),
     *
     *     @SWG\Response(
     *          response=201,
     *          description="Model created successfully",
     *
     *          @SWG\Schema(ref="#/definitions/Subscription"),
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
     *          response="default",
     *          ref="#/responses/Standard500ErrorResponse"
     *      ),
     * )
     *
     * @return JsonResponse
     */
    public function store(Requests\Entity\Subscription\StoreRequest $request, IsAnEntityContract $entity)
    {
        $model = $this->entitySubscriptionCreationService->createSubscription($entity, $request->json()->all());

        return new JsonResponse($model, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @SWG\Patch(
     *     path="/users/{user_id}/subscriptions/{id}",
     *     summary="Updates a single user",
     *     tags={"Users", "Subscriptions"},
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
     *          name="user_id",
     *          in="path",
     *          required=true,
     *          type="integer",
     *          format="int32",
     *          description="The ID of the user model"
     *     ),
     *     @SWG\Parameter(
     *          name="model",
     *          in="body",
     *          required=true,
     *
     *          @SWG\Schema(ref="#/definitions/Subscription"),
     *          description="The model updates to make"
     *     ),
     *
     *     @SWG\Response(
     *          response=200,
     *          description="Successful update",
     *
     *          @SWG\Schema(ref="#/definitions/Subscription"),
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
     * @return Subscription|BaseModelAbstract
     */
    public function update(Requests\Entity\Subscription\UpdateRequest $request, IsAnEntityContract $entity, Subscription $subscription)
    {
        $data = $request->json()->all();

        if (isset($data['cancel']) && $data['cancel']) {
            $data['canceled_at'] = Carbon::now();
            unset($data['cancel']);
        }

        return $this->repository->update($subscription, $data);
    }
}
