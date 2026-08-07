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
use Polis\Repositories\BaseRepositoryAbstract;

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
 * currently an Organization, but generically ANY {@see IsAnEntityContract}.
 *
 * The scoping is delegated to the repository's `belongsToArray` mechanism: the
 * bound entity is handed to {@see BaseRepositoryAbstract::buildFindAllQuery()},
 * which resolves the correct relation on Article FROM THE ENTITY'S CLASS
 * (`organization()` for an Organization, `user()` for a User, …) and applies a
 * `whereHas(...)`. Because the relation is discovered from the entity rather
 * than hard-coded, the same controller works for every entity type that Article
 * can belong to — an Organization today, a User the moment Article gains a
 * `user()` relation, with no new controller.
 *
 * This is the reusable base that {@see OrganizationArticleControllerAbstract}
 * now derives from; the Organization case is just one entity type.
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
            $this->filter($request),
            $this->search($request),
            $this->order($request),
            $this->expand($request),
            $this->limit($request),
            [$entity],
            (int) $request->input('page', 1),
        );
    }
}
