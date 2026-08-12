<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\User;

use App\Models\User\User;
use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests;
use Polis\Models\BaseModelAbstract;

abstract class UserPageComponentControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    public function __construct(
        protected UserPageComponentRepositoryContract $repository,
    ) {}

    public function index(Requests\User\UserPageComponent\IndexRequest $request, User $user, UserPage $page): LengthAwarePaginator
    {
        $filter = $this->filter($request);
        $filter[] = ['user_page_id', '=', $page->id];

        return $this->repository->findAll(
            $filter,
            $this->search($request),
            ['display_order' => 'asc'],
            [],
            $this->limit($request),
            [],
            (int) $request->input('page', 1)
        );
    }

    public function store(Requests\User\UserPageComponent\StoreRequest $request, User $user, UserPage $page): JsonResponse
    {
        $data = $request->json()->all();
        $data['user_page_id'] = $page->id;

        if (! isset($data['display_order'])) {
            $maxOrder = $this->repository->findAll([
                ['user_page_id', '=', $page->id],
            ])->max('display_order');
            $data['display_order'] = ($maxOrder ?? 0) + 1;
        }

        $model = $this->repository->create($data);

        return response()->json($model, 201);
    }

    public function update(Requests\User\UserPageComponent\UpdateRequest $request, User $user, UserPage $page, UserPageComponent $component): BaseModelAbstract
    {
        $data = $request->json()->all();

        return $this->repository->update($component, $data);
    }

    public function destroy(Requests\User\UserPageComponent\DeleteRequest $request, User $user, UserPage $page, UserPageComponent $component): JsonResponse
    {
        $this->repository->delete($component);

        return response()->json(null, 204);
    }
}
