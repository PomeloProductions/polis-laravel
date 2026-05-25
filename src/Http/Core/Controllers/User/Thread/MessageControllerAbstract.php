<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\User\Thread;

use App\Http\Core\Requests;
use App\Models\Messaging\Message;
use App\Models\Messaging\Thread;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Models\BaseModelAbstract;

/**
 * Class MessageControllerAbstract
 */
abstract class MessageControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    /**
     * @var MessageRepositoryContract
     */
    private $repository;

    /**
     * MessageController constructor.
     */
    public function __construct(MessageRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return LengthAwarePaginator
     */
    public function index(Requests\User\Thread\Message\IndexRequest $request, User $user, Thread $thread)
    {
        $order = $this->order($request);

        if (! count($order)) {
            $order['created_at'] = 'desc';
        }

        return $this->repository->findAll($this->filter($request), $this->search($request), $order, $this->expand($request), $this->limit($request), [$thread], (int) $request->input('page', 1));
    }

    public function store(Requests\User\Thread\Message\StoreRequest $request, User $user, Thread $thread): JsonResponse
    {
        $message = $request->json('message');
        $data = [
            'from_id' => $user->id,
            'thread_id' => $thread->id,
            'via' => [Message::VIA_PUSH_NOTIFICATION],
            'data' => [
                'body' => $message,
                'title' => 'New message from '.$user->first_name,
            ],
            'action' => '/user/'.$user->id.'/message',
        ];

        return new JsonResponse($this->repository->create($data), 201);
    }

    /**
     * Updates a message, mostly used to set the message as seen
     *
     * @return BaseModelAbstract
     *
     * @throws \Exception
     */
    public function update(Requests\User\Thread\Message\UpdateRequest $request, User $user, Thread $thread, Message $message)
    {
        $requestData = $request->json()->all();

        $data = [];

        if (isset($requestData['seen'])) {
            $data['seen_at'] = new Carbon;
        }

        return $this->repository->update($message, $data);
    }
}
