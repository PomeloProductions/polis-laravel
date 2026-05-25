<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\Subscription;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Polis\Contracts\Repositories\BaseRepositoryContract;

/**
 * Interface SubscriptionRepositoryContract
 */
interface SubscriptionRepositoryContract extends BaseRepositoryContract
{
    /**
     * Finds all subscriptions that expire at a certain date
     */
    public function findExpiring(Carbon $expiresAt): Collection;

    /**
     * Finds all subscriptions that expire after the passed in expiration date
     *  The optional type field will filter out all subscriptions that are not to a specific subscriber type
     */
    public function findExpiresAfter(Carbon $expirationDate, ?string $type = null): Collection;
}
