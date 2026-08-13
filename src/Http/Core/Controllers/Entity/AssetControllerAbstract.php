<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Entity;

use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Mimey\MimeTypes;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\AssetRepositoryContract;
use Polis\Http\Core\Requests;
use Polis\Http\Core\Requests\BaseRequestAbstract;
use Polis\Models\BaseModelAbstract;

/**
 * Class AssetControllerAbstract
 *
 * Entity-scoped asset management. A thin specialization of
 * {@see EntityResourceControllerAbstract}: it inherits the generic polymorphic
 * owner scoping, owner stamping, update and destroy, and only overrides
 * {@see storeData()} to attach the decoded file contents + resolved extension.
 */
abstract class AssetControllerAbstract extends EntityResourceControllerAbstract
{
    /**
     * @var MimeTypes
     */
    private $mimeTypes;

    /**
     * AssetController constructor.
     */
    public function __construct(AssetRepositoryContract $repository, MimeTypes $mimeTypes)
    {
        parent::__construct($repository);
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * Gets all assets for an entity
     *
     * @return LengthAwarePaginator
     */
    public function index(Requests\Entity\Asset\IndexRequest $request, IsAnEntityContract $entity)
    {
        return $this->indexForEntity($request, $entity);
    }

    /**
     * Attaches the decoded file contents and resolved extension to the payload.
     */
    protected function storeData(BaseRequestAbstract $request, IsAnEntityContract $entity): array
    {
        $data = parent::storeData($request, $entity);

        $data['file_contents'] = $request->getDecodedContents();
        $data['file_extension'] = $this->mimeTypes->getExtension($request->getFileMimeType());

        return $data;
    }

    /**
     * Creates the new asset for us
     */
    public function store(Requests\Entity\Asset\StoreRequest $request, IsAnEntityContract $entity): JsonResponse
    {
        return $this->storeForEntity($request, $entity);
    }

    /**
     * Updates an asset properly
     *
     * @return BaseModelAbstract
     */
    public function update(Requests\Entity\Asset\UpdateRequest $request, IsAnEntityContract $entity, Asset $asset)
    {
        return $this->updateForEntity($request, $entity, $asset);
    }

    /**
     * Deletes an asset from the server
     *
     * @return ResponseFactory|Response
     */
    public function destroy(Requests\Entity\Asset\DeleteRequest $request, IsAnEntityContract $entity, Asset $asset): Response
    {
        return $this->destroyForEntity($request, $entity, $asset);
    }
}
