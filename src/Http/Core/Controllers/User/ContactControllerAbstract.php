<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\User;

use App\Models\User\Contact;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Repositories\User\ContactRepositoryContract;
use Polis\Events\User\Contact\ContactCreatedEvent;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests;
use Polis\Models\BaseModelAbstract;

/**
 * Class ContactControllerAbstract
 */
abstract class ContactControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * @var ContactRepositoryContract
     */
    private $repository;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * ContactController constructor.
     */
    public function __construct(ContactRepositoryContract $repository, Dispatcher $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return LengthAwarePaginator
     */
    public function index(Requests\User\Contact\IndexRequest $request, User $user)
    {
        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [$user], (int) $request->input('page', 1));
    }

    /**
     * @return JsonResponse
     */
    public function store(Requests\User\Contact\StoreRequest $request, User $user)
    {
        $data = $request->json()->all();

        $data['initiated_by_id'] = $user->id;

        /** @var Contact $model */
        $model = $this->repository->create($data);

        $this->dispatcher->dispatch(new ContactCreatedEvent($model));

        return new JsonResponse($model, 201);
    }

    /**
     * Updates an event participant, mostly used to link assets
     *
     * @return BaseModelAbstract
     *
     * @throws \Exception
     */
    public function update(Requests\User\Contact\UpdateRequest $request, User $user, Contact $contact)
    {
        $requestData = $request->json()->all();

        $data = [];

        if (isset($requestData['confirm'])) {
            $data['confirmed_at'] = new Carbon;
        }
        if (isset($requestData['deny'])) {
            $data['denied_at'] = new Carbon;
        }

        return $this->repository->update($contact, $data);
    }
}
