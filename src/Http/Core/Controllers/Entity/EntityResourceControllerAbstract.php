<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Entity;

use App\Models\Role;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\BaseRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests\BaseRequestAbstract;
use Polis\Http\Core\Requests\Entity\Traits\IsEntityRequestTrait;
use Polis\Models\BaseModelAbstract;

/**
 * Class EntityResourceControllerAbstract
 *
 * A reusable base for any resource that is owned by an {@see IsAnEntityContract}
 * entity (currently a User or an Organization) through the polymorphic
 * `owner_id` / `owner_type` columns — the exact pattern Payment, Collection,
 * Asset, PaymentMethod, and Subscription already use.
 *
 * Rather than hand-rolling the "stamp the owner + scope the listing to the
 * owner" boilerplate on every entity-owned resource (as the pre-existing
 * Collection/Asset controllers did), a concrete controller can extend this base
 * and delegate its actions to the protected helpers below. The owning entity is
 * resolved generically — it is always the first route parameter, per
 * {@see IsEntityRequestTrait} — so the
 * SAME helpers serve a User-owned OR an Organization-owned resource (and any
 * future entity type) with no per-owner subclass.
 *
 * The helpers are `protected` (not the public REST actions themselves) on
 * purpose: PHP requires parameter types on an overriding method to be
 * contravariant, so a concrete controller cannot legally re-declare
 * `index(ConcreteRequest $r, Organization $e)` over a base
 * `index(BaseRequestAbstract $r, IsAnEntityContract $e)`. Instead each concrete
 * controller keeps its own strongly-typed action (so Laravel form-request
 * injection and route model binding still work) and forwards to
 * `indexForEntity()` / `storeForEntity()` / `updateForEntity()` /
 * `destroyForEntity()`. See {@see CollectionControllerAbstract} and
 * {@see AssetControllerAbstract} for the pattern.
 *
 * Everything is overridable: a subclass may narrow the listing further (see
 * {@see CollectionControllerAbstract}'s `is_public` gate) or inject derived
 * store fields (see {@see AssetControllerAbstract}'s `storeData()`).
 */
abstract class EntityResourceControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * EntityResourceController constructor.
     */
    public function __construct(protected BaseRepositoryContract $repository) {}

    /**
     * The role required to view/manage this entity's resources when narrowing
     * a listing. Subclasses may override to require ADMINISTRATOR, etc.
     */
    protected function managementRole(): int
    {
        return Role::MANAGER;
    }

    /**
     * Builds the base filter for a listing, always scoping to the owning
     * entity's polymorphic owner columns. Subclasses may override to append
     * additional constraints (e.g. an `is_public` gate for non-managers).
     */
    protected function entityFilter(BaseRequestAbstract $request, IsAnEntityContract $entity): array
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

        return $filter;
    }

    /**
     * Whether the currently logged-in user manages the given entity at the
     * configured management role. Handy for subclasses that gate visibility.
     */
    protected function loggedInUserManagesEntity(IsAnEntityContract $entity): bool
    {
        /** @var User|null $loggedInUser */
        $loggedInUser = Auth::user();

        return $loggedInUser && $entity->canUserManageEntity($loggedInUser, $this->managementRole());
    }

    /**
     * Display a listing of the entity's resources, scoped to the entity's
     * polymorphic owner columns.
     *
     * @return LengthAwarePaginator
     */
    protected function indexForEntity(BaseRequestAbstract $request, IsAnEntityContract $entity)
    {
        return $this->repository->findAll(
            $this->entityFilter($request, $entity),
            $this->search($request),
            $this->order($request),
            $this->expand($request),
            $this->limit($request),
            [],
            (int) $request->input('page', 1),
        );
    }

    /**
     * Store a newly created resource for the entity, stamping the polymorphic
     * owner.
     */
    protected function storeForEntity(BaseRequestAbstract $request, IsAnEntityContract $entity): JsonResponse
    {
        $data = $this->storeData($request, $entity);

        $data['owner_id'] = $entity->id;
        $data['owner_type'] = $entity->morphRelationName();

        return new JsonResponse($this->repository->create($data), 201);
    }

    /**
     * The payload used when creating the resource. Subclasses that need to
     * inject additional derived fields (file contents, mime type, etc.) should
     * override this rather than storeForEntity().
     */
    protected function storeData(BaseRequestAbstract $request, IsAnEntityContract $entity): array
    {
        return $request->json()->all();
    }

    /**
     * Update the given resource.
     *
     * @return BaseModelAbstract
     */
    protected function updateForEntity(BaseRequestAbstract $request, IsAnEntityContract $entity, BaseModelAbstract $model)
    {
        return $this->repository->update($model, $request->json()->all());
    }

    /**
     * Remove the given resource.
     *
     * @return Response
     */
    protected function destroyForEntity(BaseRequestAbstract $request, IsAnEntityContract $entity, BaseModelAbstract $model)
    {
        $this->repository->delete($model);

        return response(null, 204);
    }
}
