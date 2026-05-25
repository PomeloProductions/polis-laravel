<?php

declare(strict_types=1);

namespace Polis\Repositories\Messaging;

use App\Models\Messaging\Thread;
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
     * Links the users properly
     *
     * @return BaseModelAbstract|Thread
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = [])
    {
        $users = $this->getAndUnset($data, 'users', []);

        /** @var Thread $thread */
        $thread = parent::create($data, $relatedModel, $forcedValues);

        $thread->users()->sync($users);

        return $thread;
    }
}
