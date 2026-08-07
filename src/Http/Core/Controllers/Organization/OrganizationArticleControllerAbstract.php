<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Organization;

use App\Http\Core\Requests;
use App\Models\Organization\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Http\Core\Controllers\ArticleControllerAbstract;
use Polis\Http\Core\Controllers\Entity\EntityArticleControllerAbstract;
use Polis\Http\Core\Requests\Organization\Article\IndexRequest;
use Polis\Repositories\BaseRepositoryAbstract;

/**
 * Class OrganizationArticleControllerAbstract
 *
 * The Organization flavour of the entity-scoped Article ("contract") listing
 * for the dashboard Organization-detail page:
 *
 *     GET /organizations/{organization}/articles
 *
 * As of the Polis 0.3 User→Entity generalization this is a THIN subclass of
 * {@see EntityArticleControllerAbstract}: an Organization is simply one
 * {@see IsAnEntityContract} entity, so all of the
 * `belongsToArray` scoping lives in the reusable base and the same logic serves
 * a User-owned (or any future entity-owned) article listing. This subclass
 * survives so that consumers already extending it (client-driver's
 * `OrganizationArticleController` shim) keep working unchanged, and so the route
 * model binding can continue to type-hint a concrete {@see Organization}.
 *
 * Unlike the platform-wide {@see ArticleControllerAbstract} (which lists every
 * Article in the wiki), this scopes the result set to Articles whose
 * `organization_id` matches the organization in the route. The scoping is
 * handled by the repository's `belongsToArray` mechanism: passing the bound
 * Organization makes {@see BaseRepositoryAbstract::buildFindAllQuery()} apply
 * `whereHas('organization', ...)` against Article's `organization()` relation.
 *
 * Authorization is enforced by {@see IndexRequest}, so a caller only ever sees
 * the contracts of an organization they manage (super-admins see any
 * organization's).
 */
abstract class OrganizationArticleControllerAbstract extends EntityArticleControllerAbstract
{
    /**
     * Display a listing of the organization's articles (contracts).
     *
     * @SWG\Get(
     *     path="/organizations/{organization_id}/articles",
     *     summary="List the articles (contracts) owned by an organization",
     *     tags={"Articles","Organizations"},
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
     *          @SWG\Schema(ref="#/definitions/PagedArticles"),
     *      ),
     *
     *     @SWG\Response(
     *          response=401,
     *          ref="#/responses/Standard401UnauthorizedResponse"
     *      ),
     *     @SWG\Response(
     *          response=403,
     *          ref="#/responses/Standard403ForbiddenResponse"
     *      ),
     *     @SWG\Response(
     *          response="default",
     *          ref="#/responses/Standard500ErrorResponse"
     *      ),
     * )
     *
     * @return LengthAwarePaginator
     */
    public function index(Requests\Organization\Article\IndexRequest $request, Organization $organization)
    {
        return $this->indexForEntity($request, $organization);
    }
}
