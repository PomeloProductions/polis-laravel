<?php

declare(strict_types=1);

namespace Polis\Repositories\Messaging;

use App\Models\Messaging\PushNotificationKey;
use Polis\Contracts\Repositories\Messaging\PushNotificationKeyRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Repositories\Traits\NotImplemented\Delete;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class PushNotificationKeyRepository
 */
class PushNotificationKeyRepository extends BaseRepositoryAbstract implements PushNotificationKeyRepositoryContract
{
    use Delete, \Polis\Repositories\Traits\NotImplemented\FindAll, \Polis\Repositories\Traits\NotImplemented\FindOrFail;

    /**
     * PushNotificationKeyRepository constructor.
     */
    public function __construct(PushNotificationKey $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }

    /**
     * Finds a push notification by the specific key that is passed through
     */
    public function findByPushNotificationKey(string $key): ?PushNotificationKey
    {
        return $this->model->newQuery()->where('push_notification_key', '=', $key)->first();
    }
}
