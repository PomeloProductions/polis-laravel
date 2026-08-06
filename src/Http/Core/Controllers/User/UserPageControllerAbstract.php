<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\User;

use App\Http\Core\Requests;
use App\Models\User\User;
use App\Models\User\UserPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Polis\Contracts\Repositories\User\UserPageRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Models\BaseModelAbstract;

abstract class UserPageControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    public function __construct(
        protected UserPageRepositoryContract $repository,
    ) {}

    public function index(Requests\User\UserPage\IndexRequest $request, User $user): LengthAwarePaginator
    {
        $filter = $this->filter($request);
        $filter[] = ['user_id', '=', $user->id];

        return $this->repository->findAll(
            $filter,
            $this->search($request),
            ['display_order' => 'asc'],
            $this->expand($request),
            $this->limit($request),
            [],
            (int) $request->input('page', 1)
        );
    }

    public function store(Requests\User\UserPage\StoreRequest $request, User $user): JsonResponse
    {
        $data = $request->json()->all();
        $data['user_id'] = $user->id;
        // Keep the polymorphic owner in sync with the legacy user_id FK so new
        // rows are queryable through both the /users/{user}/pages surface and
        // the entity-generic owner_id/owner_type surface.
        $data['owner_id'] = $user->id;
        $data['owner_type'] = $user->morphRelationName();

        if (! isset($data['slug'])) {
            $data['slug'] = $this->generateSlug($user, $data['name']);
        }

        if (! isset($data['display_order'])) {
            $maxOrder = $this->repository->findAll([
                ['user_id', '=', $user->id],
            ])->max('display_order');
            $data['display_order'] = ($maxOrder ?? 0) + 1;
        }

        $data['is_required'] = false;
        $data['is_visible'] = $data['is_visible'] ?? true;
        $data['icon'] = $data['icon'] ?? 'IconList';

        $model = $this->repository->create($data);

        return response()->json($model->load('components'), 201);
    }

    public function update(Requests\User\UserPage\UpdateRequest $request, User $user, UserPage $page): BaseModelAbstract
    {
        $data = $request->json()->all();

        if ($page->is_required) {
            unset($data['slug'], $data['route_path'], $data['page_type'], $data['is_required']);
        }

        return $this->repository->update($page, $data);
    }

    public function destroy(Requests\User\UserPage\DeleteRequest $request, User $user, UserPage $page): JsonResponse
    {
        $this->repository->delete($page);

        return response()->json(null, 204);
    }

    protected function generateSlug(User $user, string $name): string
    {
        $baseSlug = Str::slug($name, '-');

        if (preg_match('/^[0-9]/', $baseSlug)) {
            $baseSlug = 'page-'.$baseSlug;
        }

        $slug = $baseSlug;
        $counter = 1;

        while ($this->repository->findAll([
            ['user_id', '=', $user->id],
            ['slug', '=', $slug],
        ])->isNotEmpty()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
