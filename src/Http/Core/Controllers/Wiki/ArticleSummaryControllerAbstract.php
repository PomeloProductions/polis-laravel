<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleSummary;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Repositories\Wiki\ArticleSummaryRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Requests;

/**
 * Class ArticleSummaryControllerAbstract
 */
abstract class ArticleSummaryControllerAbstract extends BaseControllerAbstract
{
    private ArticleSummaryRepositoryContract $repository;

    /**
     * ArticleSummaryController constructor.
     */
    public function __construct(ArticleSummaryRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display the summary for the article
     */
    public function show(Requests\Wiki\ArticleSummary\ViewRequest $request, Article $article): JsonResponse
    {
        $summary = $article->articleSummary;

        if (! $summary) {
            return new JsonResponse([
                'message' => 'Article summary not found.',
            ], 404);
        }

        return new JsonResponse($summary, 200);
    }

    /**
     * Create a new summary for the article
     */
    public function store(Requests\Wiki\ArticleSummary\StoreRequest $request, Article $article): JsonResponse
    {
        $data = $request->json()->all();
        $data['article_id'] = $article->id;

        /** @var ArticleSummary $model */
        $model = $this->repository->create($data);

        return new JsonResponse($model, 201);
    }

    /**
     * Update the article summary
     */
    public function update(Requests\Wiki\ArticleSummary\UpdateRequest $request, Article $article): JsonResponse
    {
        $summary = $article->articleSummary;

        if (! $summary) {
            return new JsonResponse([
                'message' => 'Article summary not found.',
            ], 404);
        }

        $data = $request->json()->all();

        /** @var ArticleSummary $updated */
        $updated = $this->repository->update($summary, $data);

        return new JsonResponse($updated, 200);
    }

    /**
     * Delete the article summary
     */
    public function destroy(Requests\Wiki\ArticleSummary\DeleteRequest $request, Article $article): JsonResponse
    {
        $summary = $article->articleSummary;

        if (! $summary) {
            return new JsonResponse([
                'message' => 'Article summary not found.',
            ], 404);
        }

        $this->repository->delete($summary);

        return new JsonResponse(null, 204);
    }
}
