<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use App\Models\Organization\Organization;
use Illuminate\Support\Facades\Auth;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Http\Core\Requests;

/**
 * Class OrganizationControllerAbstract
 */
abstract class MessageControllerAbstract extends BaseControllerAbstract
{
    /**
     * OrganizationController constructor.
     */
    public function __construct(
        protected MessageRepositoryContract $repository,
    ) {}

    /**
     * Store a newly created resource in storage.
     *
     * @return Organization
     */
    public function store(Requests\Message\StoreRequest $request)
    {
        $data = $request->json()->all();

        if ($user = Auth::user()) {
            $data['from_type'] = 'user';
            $data['from_id'] = $user->id;
        }

        if (! isset($data['reply_to_email']) && isset($data['data']['email'])) {
            $data['reply_to_email'] = $data['data']['email'];
        }
        if (! isset($data['reply_to_name'])) {
            $parts = [];
            if (isset($data['data']['name'])) {
                $parts[] = $data['data']['name'];
            } else {
                if (isset($data['data']['first_name'])) {
                    $parts[] = $data['data']['first_name'];
                }
                if (isset($data['data']['last_name'])) {
                    $parts[] = $data['data']['last_name'];
                }
            }
            $data['reply_to_name'] = implode(' ', $parts);
        }

        $model = $this->repository->create($data);

        return response($model, 201);
    }
}
