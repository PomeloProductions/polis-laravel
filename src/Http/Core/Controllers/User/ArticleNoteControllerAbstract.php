<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\User;

use App\Http\Core\Requests;
use App\Models\User\ArticleNote;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Repositories\User\ArticleNoteRepositoryContract;
use Polis\Contracts\Repositories\Wiki\ArticleRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Models\BaseModelAbstract;

/**
 * Class ArticleNoteControllerAbstract
 */
abstract class ArticleNoteControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    private ArticleNoteRepositoryContract $repository;

    private ArticleRepositoryContract $articleRepository;

    /**
     * ArticleNoteController constructor.
     */
    public function __construct(ArticleNoteRepositoryContract $repository, ArticleRepositoryContract $articleRepository)
    {
        $this->repository = $repository;
        $this->articleRepository = $articleRepository;
    }

    public function index(Requests\User\ArticleNote\IndexRequest $request, User $user): LengthAwarePaginator
    {
        return $this->repository->findAll(
            $this->filter($request),
            $this->search($request),
            $this->order($request),
            $this->expand($request),
            $this->limit($request),
            [$user],
            (int) $request->input('page', 1)
        );
    }

    public function store(Requests\User\ArticleNote\StoreRequest $request, User $user): JsonResponse
    {
        $data = $request->json()->all();
        $data['user_id'] = $user->id;
        // Keep the polymorphic owner in sync with the legacy user_id FK.
        $data['owner_id'] = $user->id;
        $data['owner_type'] = $user->morphRelationName();

        /** @var ArticleNote $model */
        $model = $this->repository->create($data);

        return new JsonResponse($model, 201);
    }

    public function show(Requests\User\ArticleNote\ViewRequest $request, User $user, ArticleNote $articleNote): ArticleNote
    {
        return $articleNote;
    }

    public function update(Requests\User\ArticleNote\UpdateRequest $request, User $user, ArticleNote $articleNote): BaseModelAbstract
    {
        $data = $request->json()->all();

        return $this->repository->update($articleNote, $data);
    }

    public function destroy(Requests\User\ArticleNote\DeleteRequest $request, User $user, ArticleNote $articleNote): JsonResponse
    {
        $this->repository->delete($articleNote);

        return new JsonResponse(null, 204);
    }

    /**
     * Selects a random article for the user and creates or retrieves an article note
     */
    public function randomArticle(Requests\User\ArticleNote\RandomArticleRequest $request, User $user): JsonResponse
    {
        $article = $this->articleRepository->selectArticleForUser($user);

        if (! $article) {
            return new JsonResponse([
                'message' => 'No available articles found.',
            ], 404);
        }

        // Check if a note already exists for this article
        $existingNote = ArticleNote::where('user_id', $user->id)
            ->where('article_id', $article->id)
            ->first();

        if ($existingNote) {
            $existingNote->load('article');

            return new JsonResponse($existingNote, 200);
        }

        /** @var ArticleNote $articleNote */
        $articleNote = $this->repository->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        $articleNote->load('article');

        return new JsonResponse($articleNote, 201);
    }
}
