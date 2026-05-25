<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Entity;

use App\Http\Core\Requests;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Mimey\MimeTypes;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\AssetRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Models\BaseModelAbstract;

/**
 * Class AssetControllerAbstract
 */
abstract class AssetControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * @var AssetRepositoryContract
     */
    private $repository;

    /**
     * @var MimeTypes
     */
    private $mimeTypes;

    /**
     * AssetController constructor.
     */
    public function __construct(AssetRepositoryContract $repository, MimeTypes $mimeTypes)
    {
        $this->repository = $repository;
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * Gets all assets for a user
     *
     * @return LengthAwarePaginator
     */
    public function index(Requests\Entity\Asset\IndexRequest $request, IsAnEntityContract $entity)
    {
        $filter = $this->filter($request);

        $filter[] = [
            'owner_id',
            '=',
            $entity->id,
        ];
        $filter[] = [
            'owner_type',
            '=',
            $entity->morphRelationName(),
        ];

        return $this->repository->findAll($filter, $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [], (int) $request->input('page', 1));
    }

    /**
     * Creates the new asset for us
     *
     * @return JsonResponse
     */
    public function store(Requests\Entity\Asset\StoreRequest $request, IsAnEntityContract $entity)
    {
        $data = $request->json()->all();

        $data['file_contents'] = $request->getDecodedContents();
        $data['file_extension'] = $this->mimeTypes->getExtension($request->getFileMimeType());

        $data['owner_id'] = $entity->id;
        $data['owner_type'] = $entity->morphRelationName();

        $model = $this->repository->create($data);

        return new JsonResponse($model, 201);
    }

    /**
     * Updates an asset properly
     *
     * @return BaseModelAbstract
     */
    public function update(Requests\Entity\Asset\UpdateRequest $request, IsAnEntityContract $entity, Asset $asset)
    {
        return $this->repository->update($asset, $request->json()->all());
    }

    /**
     * Deletes an asset from the server
     *
     * @return ResponseFactory|Response
     */
    public function destroy(Requests\Entity\Asset\DeleteRequest $request, IsAnEntityContract $entity, Asset $asset)
    {
        $this->repository->delete($asset);

        return response(null, 204);
    }
}
