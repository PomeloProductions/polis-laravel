<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Entity;

use App\Http\Core\Requests;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Collection\CollectionRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;

/**
 * Class ThreadControllerAbstract
 */
abstract class CollectionControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * ThreadController constructor.
     */
    public function __construct(protected CollectionRepositoryContract $repository) {}

    /**
     * @param  User  $user
     * @return LengthAwarePaginator
     */
    public function index(Requests\Entity\Collection\IndexRequest $request, IsAnEntityContract $entity)
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

        /** @var User $loggedInUser */
        $loggedInUser = Auth::user();

        if (! $loggedInUser || ! $entity->canUserManageEntity($loggedInUser, Role::MANAGER)) {
            $filter[] = [
                'is_public',
                '=',
                '1',
            ];
        }

        return $this->repository->findAll($filter, $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [], (int) $request->input('page', 1));
    }

    /**
     * @param  User  $user
     */
    public function store(Requests\Entity\Collection\StoreRequest $request, IsAnEntityContract $entity): JsonResponse
    {
        $data = $request->json()->all();

        $data['owner_id'] = $entity->id;
        $data['owner_type'] = $entity->morphRelationName();

        return new JsonResponse($this->repository->create($data), 201);
    }
}
