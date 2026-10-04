<?php

declare(strict_types=1);

namespace Polis\Repositories\Messaging;

use App\Models\Messaging\Thread;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\Messaging\ThreadRepositoryContract;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Traits\CanGetAndUnset;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class ThreadRepository
 */
class ThreadRepository extends BaseRepositoryAbstract implements ThreadRepositoryContract
{
    use CanGetAndUnset, \Polis\Repositories\Traits\NotImplemented\Update;

    /**
     * ThreadRepository constructor.
     */
    public function __construct(Thread $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }

    /**
     * Override findAll to eager-load the latest message that the `last_message`
     * append reads, scoping the N+1 fix to the listing path only (it is
     * intentionally NOT on the model's `$with`).
     */
    public function findAll(array $filters = [], array $searches = [], array $orderBy = [], array $with = [], $limit = 10, array $belongsToArray = [], int $page = 1): LengthAwarePaginator|Collection
    {
        $with = array_values(array_unique(['latestMessage', ...$with]));

        return parent::findAll($filters, $searches, $orderBy, $with, $limit, $belongsToArray, $page);
    }

    /**
     * Links the users properly
     *
     * @return BaseModelAbstract|Thread
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = []): BaseModelAbstract
    {
        $users = $this->getAndUnset($data, 'users', []);

        /** @var Thread $thread */
        $thread = parent::create($data, $relatedModel, $forcedValues);

        $thread->users()->sync($users);

        return $thread;
    }
}
