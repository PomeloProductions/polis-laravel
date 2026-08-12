<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use App\Models\User\InvitationToken;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Repositories\User\InvitationTokenRepositoryContract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests;
use Polis\Models\BaseModelAbstract;

/**
 * Class InvitationTokenControllerAbstract
 */
abstract class InvitationTokenControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    private InvitationTokenRepositoryContract $repository;

    /**
     * InvitationTokenController constructor.
     */
    public function __construct(InvitationTokenRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of invitation tokens.
     */
    public function index(Requests\InvitationToken\IndexRequest $request): LengthAwarePaginator
    {
        return $this->repository->findAll(
            $this->filter($request),
            $this->search($request),
            $this->order($request),
            $this->expand($request),
            $this->limit($request),
            [],
            (int) $request->input('page', 1)
        );
    }

    /**
     * Store a newly created invitation token.
     */
    public function store(Requests\InvitationToken\StoreRequest $request): JsonResponse
    {
        $data = $request->json()->all();
        $data['token'] = $this->repository->generateUniqueToken();

        /** @var InvitationToken $model */
        $model = $this->repository->create($data);

        return new JsonResponse($model, 201);
    }

    /**
     * Display the specified invitation token.
     */
    public function show(Requests\InvitationToken\ViewRequest $request, InvitationToken $invitationToken): InvitationToken
    {
        return $invitationToken;
    }

    /**
     * Update the specified invitation token.
     */
    public function update(Requests\InvitationToken\UpdateRequest $request, InvitationToken $invitationToken): BaseModelAbstract
    {
        $data = $request->json()->all();

        return $this->repository->update($invitationToken, $data);
    }

    /**
     * Remove the specified invitation token.
     */
    public function destroy(Requests\InvitationToken\DeleteRequest $request, InvitationToken $invitationToken): JsonResponse
    {
        $this->repository->delete($invitationToken);

        return new JsonResponse(null, 204);
    }
}
