<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\Messaging;

use App\Models\Messaging\PushNotificationKey;
use Polis\Contracts\Repositories\BaseRepositoryContract;

/**
 * Interface PushNotificationKeyRepositoryContract
 */
interface PushNotificationKeyRepositoryContract extends BaseRepositoryContract
{
    /**
     * Finds a push notification by the specific key that is passed through
     */
    public function findByPushNotificationKey(string $key): ?PushNotificationKey;
}
