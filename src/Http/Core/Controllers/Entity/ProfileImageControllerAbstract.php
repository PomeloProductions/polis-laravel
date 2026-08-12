<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Entity;

use Illuminate\Http\JsonResponse;
use Mimey\MimeTypes;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\User\ProfileImageRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Requests;

/**
 * Class ProfileImageControllerAbstract
 */
abstract class ProfileImageControllerAbstract extends BaseControllerAbstract
{
    /**
     * @var ProfileImageRepositoryContract
     */
    private $repository;

    /**
     * @var MimeTypes
     */
    private $mimeTypes;

    /**
     * ProfileImagesController constructor.
     */
    public function __construct(ProfileImageRepositoryContract $repository, MimeTypes $mimeTypes)
    {
        $this->repository = $repository;
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @SWG\Post(
     *     path="/users/{user_id}/profile-images",
     *     summary="Create a new user profile image",
     *     tags={"Users","ProfileImages"},
     *
     *     @SWG\Parameter(ref="#/parameters/AuthorizationHeader"),
     *     @SWG\Parameter(
     *          name="user_id",
     *          in="path",
     *          required=true,
     *          type="integer",
     *          format="int32",
     *          description="The ID of the owning model"
     *     ),
     *     @SWG\Parameter(
     *          name="model",
     *          in="body",
     *          required=true,
     *
     *          @SWG\Schema(ref="#/definitions/ProfileImage"),
     *          description="The model to create"
     *     ),
     *
     *     @SWG\Response(
     *          response=201,
     *          description="Model created successfully",
     *
     *          @SWG\Schema(ref="#/definitions/ProfileImage"),
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
    public function store(Requests\Entity\ProfileImage\StoreRequest $request, IsAnEntityContract $entity)
    {
        $data = [];

        $data['file_contents'] = $request->getDecodedContents();
        $data['file_extension'] = $this->mimeTypes->getExtension($request->getFileMimeType());

        $data['owner_id'] = $entity->id;
        $data['owner_type'] = $entity->morphRelationName();

        $model = $this->repository->create($data, $entity);

        return new JsonResponse($model, 201);
    }
}
