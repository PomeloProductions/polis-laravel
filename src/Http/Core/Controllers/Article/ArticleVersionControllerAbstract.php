<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Article;

use App\Models\Wiki\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Repositories\Wiki\ArticleVersionRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests;

/**
 * Class ArticleVersionControllerAbstract
 */
abstract class ArticleVersionControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * @var ArticleVersionRepositoryContract
     */
    private $repository;

    /**
     * ArticleVersionController constructor.
     */
    public function __construct(ArticleVersionRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Loads all created version for the related article
     *
     * @return LengthAwarePaginator
     */
    public function index(Requests\Article\ArticleVersion\IndexRequest $request, Article $article)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [$article], (int) $request->input('page', 1));
    }

    /**
     * Creates a new article version
     */
    public function store(Requests\Article\ArticleVersion\StoreRequest $request, Article $article): JsonResponse
    {
        $data = $request->json()->all();

        return new JsonResponse($this->repository->create($data, $article), 201);
    }
}
