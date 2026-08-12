<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Entity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Collection\CollectionRepositoryContract;
use Polis\Http\Core\Requests;
use Polis\Http\Core\Requests\BaseRequestAbstract;

/**
 * Class CollectionControllerAbstract
 *
 * Entity-scoped listing/creation of Collections. This is a thin specialization
 * of {@see EntityResourceControllerAbstract}: it inherits the generic
 * polymorphic owner scoping + owner stamping, and only layers on the one
 * Collection-specific wrinkle — non-managers of the entity may only see
 * `is_public` collections.
 */
abstract class CollectionControllerAbstract extends EntityResourceControllerAbstract
{
    /**
     * CollectionController constructor.
     */
    public function __construct(CollectionRepositoryContract $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Appends the `is_public` gate for viewers who do not manage the entity.
     */
    protected function entityFilter(BaseRequestAbstract $request, IsAnEntityContract $entity): array
    {
        $filter = parent::entityFilter($request, $entity);

        if (! $this->loggedInUserManagesEntity($entity)) {
            $filter[] = [
                'is_public',
                '=',
                '1',
            ];
        }

        return $filter;
    }

    /**
     * @return LengthAwarePaginator
     */
    public function index(Requests\Entity\Collection\IndexRequest $request, IsAnEntityContract $entity)
    {
        return $this->indexForEntity($request, $entity);
    }

    public function store(Requests\Entity\Collection\StoreRequest $request, IsAnEntityContract $entity): JsonResponse
    {
        return $this->storeForEntity($request, $entity);
    }
}
