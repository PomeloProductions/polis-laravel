<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Organization;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Polis\Contracts\Repositories\Organization\OrganizationManagerRepositoryContract;
use Polis\Contracts\Repositories\User\InvitationTokenRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Events\Organization\OrganizationManagerCreatedEvent;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests;
use Polis\Models\BaseModelAbstract;
use Polis\Models\User\InvitationToken;
use Polis\Traits\CanGetAndUnset;

/**
 * Class OrganizationManagerControllerAbstract
 */
abstract class OrganizationManagerControllerAbstract extends BaseControllerAbstract
{
    use CanGetAndUnset, HasIndexRequests;

    /**
     * @var OrganizationManagerRepositoryContract
     */
    private $repository;

    /**
     * @var UserRepositoryContract
     */
    private $userRepository;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var InvitationTokenRepositoryContract
     */
    private $invitationTokenRepository;

    /**
     * OrganizationController constructor.
     */
    public function __construct(OrganizationManagerRepositoryContract $repository,
        UserRepositoryContract $userRepository,
        Dispatcher $dispatcher,
        InvitationTokenRepositoryContract $invitationTokenRepository)
    {
        $this->repository = $repository;
        $this->userRepository = $userRepository;
        $this->dispatcher = $dispatcher;
        $this->invitationTokenRepository = $invitationTokenRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return LengthAwarePaginator
     */
    public function index(Requests\Organization\OrganizationManager\IndexRequest $request, Organization $organization)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [$organization], (int) $request->input('page', 1));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return OrganizationManager
     */
    public function store(Requests\Organization\OrganizationManager\StoreRequest $request, Organization $organization)
    {
        $data = $request->json()->all();

        $email = $this->getAndUnset($data, 'email');
        $user = $this->userRepository->findByEmail($email);
        $invitationToken = null;

        if (! $user) {
            // The invitee has no account yet. Create a placeholder account with
            // an unusable random password — they cannot log in until they set
            // their own password through the accept-invitation flow — and mint
            // an invitation token carrying the org role so acceptance restores
            // the correct role. The token is emailed as an accept link (see
            // OrganizationManagerCreatedListener + InvitationUrlService).
            $user = $this->userRepository->create([
                'email' => $email,
                'password' => Str::random(32),
            ]);

            /** @var InvitationToken $invitationToken */
            $invitationToken = $this->invitationTokenRepository->create([
                'token' => $this->invitationTokenRepository->generateUniqueToken(),
                'role_id' => $data['role_id'] ?? null,
            ]);
        }

        $data['user_id'] = $user->id;

        /** @var OrganizationManager $model */
        $model = $this->repository->create($data, $organization);

        $inviter = $request->user();
        $inviterName = $inviter
            ? trim(($inviter->first_name ?? '').' '.($inviter->last_name ?? '')) ?: ($inviter->email ?? null)
            : null;

        $this->dispatcher->dispatch(new OrganizationManagerCreatedEvent($model, null, $invitationToken, $inviterName));

        return response($model, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return BaseModelAbstract
     */
    public function update(Requests\Organization\OrganizationManager\UpdateRequest $request, Organization $organization, OrganizationManager $model)
    {
        return $this->repository->update($model, $request->json()->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return null
     */
    public function destroy(Requests\Organization\OrganizationManager\DeleteRequest $request, Organization $organization, OrganizationManager $model)
    {
        $this->repository->delete($model);

        return response(null, 204);
    }
}
