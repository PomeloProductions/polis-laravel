<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use App\Models\Wiki\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Polis\Contracts\Repositories\Wiki\ArticleRepositoryContract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\Wiki\ArticleRepository;

/**
 * Class ArticleControllerAbstract
 */
abstract class ArticleControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * @var ArticleRepository
     */
    private $repository;

    /**
     * ArticleController constructor.
     */
    public function __construct(ArticleRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource
     *
     * @SWG\Get(
     *     path="/articles",
     *     summary="Get all articles",
     *     tags={"Articles"},
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
     *          ref="#/responses/Standard404PagingRequestTooLarge"
     *      ),
     *     @SWG\Response(
     *          response="default",
     *          ref="#/responses/Standard500ErrorResponse"
     *      ),
     * )
     *
     * @SWG\Definition(
     *     definition="PagedArticles",
     *     allOf={
     *
     *          @SWG\Schema(ref="#/definitions/Articles"),
     *          @SWG\Schema(ref="#/definitions/Paging")
     *     }
     * )
     *
     * @return LengthAwarePaginator
     */
    public function index(Requests\Article\IndexRequest $request)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [], (int) $request->input('page', 1));
    }

    /**
     * Display the specified resource.
     *
     * @SWG\Get(
     *     path="/articles/{id}",
     *     summary="Get a single article",
     *     tags={"Articles"},
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
     *          @SWG\Schema(ref="#/definitions/Article"),
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
     * @return Article
     */
    public function show(Requests\Article\ViewRequest $request, Article $article)
    {
        return $article->load($this->expand($request));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @SWG\Post(
     *     path="/articles",
     *     summary="Create a new article model",
     *     tags={"Articles"},
     *
     *     @SWG\Parameter(ref="#/parameters/AuthorizationHeader"),
     *     @SWG\Parameter(
     *          name="model",
     *          in="body",
     *          required=true,
     *
     *          @SWG\Schema(ref="#/definitions/Article"),
     *          description="The model to create"
     *     ),
     *
     *     @SWG\Response(
     *          response=201,
     *          description="Model created successfully",
     *
     *          @SWG\Schema(ref="#/definitions/ExerciseContent"),
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
    public function store(Requests\Article\StoreRequest $request)
    {
        $user = Auth::user();
        $data = $request->json()->all();
        $data['created_by_id'] = $user->id;

        $model = $this->repository->create($data);

        return new JsonResponse($model, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @SWG\Patch(
     *     path="/articles/{id}",
     *     summary="Updates a single article",
     *     tags={"Articles"},
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
     *          @SWG\Schema(ref="#/definitions/Article"),
     *          description="The model updates to make"
     *     ),
     *
     *     @SWG\Response(
     *          response=200,
     *          description="Successful update",
     *
     *          @SWG\Schema(ref="#/definitions/Article"),
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
     * @return Article|BaseModelAbstract
     */
    public function update(Requests\Article\UpdateRequest $request, Article $article)
    {
        return $this->repository->update($article, $request->json()->all());
    }
}
