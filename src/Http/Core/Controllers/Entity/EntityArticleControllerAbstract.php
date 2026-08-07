<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Entity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Wiki\ArticleRepositoryContract;
use Polis\Http\Core\Controllers\ArticleControllerAbstract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Organization\OrganizationArticleControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests\BaseRequestAbstract;

/**
 * Class EntityArticleControllerAbstract
 *
 * Serves an ENTITY-scoped listing of Articles ("contracts"):
 *
 *     GET /{entity}/{entity_id}/articles
 *
 * Unlike the platform-wide {@see ArticleControllerAbstract}
 * (which lists every Article in the wiki), this controller scopes the result
 * set to the Articles owned by the entity bound to the FIRST route parameter —
 * an Organization today, but generically ANY {@see IsAnEntityContract}.
 *
 * As of the Article organization_id → polymorphic-owner conversion, the scoping
 * is done against Article's polymorphic `owner_id` / `owner_type` columns (the
 * same columns Collection/Asset/Payment use), exactly like
 * {@see EntityResourceControllerAbstract::entityFilter()}. This is uniform
 * across every entity type — an Organization or a User (or any future entity) —
 * without depending on a per-owner `belongsTo` relation existing on Article.
 * Organization-owned rows have their owner columns backfilled from the retained
 * `organization_id` FK, so the Organization listing is unchanged in behaviour.
 *
 * This is the reusable base that {@see OrganizationArticleControllerAbstract}
 * derives from; the Organization case is just one entity type.
 *
 * Authorization is enforced by the request (see the corresponding
 * IndexRequest), so a caller only ever sees the contracts of an entity they
 * manage (super-admins see any entity's).
 */
abstract class EntityArticleControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * EntityArticleController constructor.
     */
    public function __construct(protected readonly ArticleRepositoryContract $repository) {}

    /**
     * Builds the listing filter, always scoping to the owning entity's
     * polymorphic owner columns.
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
     * Display a listing of the entity's articles (contracts).
     *
     * A concrete subclass keeps its own strongly-typed `index(ConcreteRequest,
     * ConcreteEntity)` action (so form-request injection + route model binding
     * work) and forwards here. It is `protected` because PHP forbids narrowing
     * parameter types on an overriding public method — see the note on
     * {@see EntityResourceControllerAbstract}.
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
}
