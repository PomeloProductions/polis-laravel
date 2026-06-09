<?php

declare(strict_types=1);

namespace Polis\Policies\User;

use App\Models\User\User;
use Polis\Models\User\ExternalAccountConnection;
use Polis\Policies\BasePolicyAbstract;

/**
 * Class ExternalAccountConnectionPolicyAbstract
 *
 * Access rules for {@see ExternalAccountConnection}.
 *
 * Connections are private to the owning user. Every gate enforces:
 *  - the logged-in user is the same as the user whose connections are being
 *    addressed (the "requested" user in the polis policy convention), AND
 *  - for row-scoped gates, the connection's `user_id` matches.
 *
 * Super-admins still hit the BasePolicyAbstract::before() short-circuit, so
 * support staff retain visibility for incident response.
 */
abstract class ExternalAccountConnectionPolicyAbstract extends BasePolicyAbstract
{
    /**
     * Listing / index — owner only.
     */
    public function all(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    /**
     * Read a single connection — owner only AND the row must belong to the
     * requested user.
     */
    public function view(User $loggedInUser, User $requestedUser, ExternalAccountConnection $connection): bool
    {
        return $loggedInUser->id === $requestedUser->id
            && $connection->user_id === $requestedUser->id;
    }

    /**
     * Create a connection — owner only. The connection row will be wired up
     * to the requested user at the controller / repository layer.
     */
    public function create(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    /**
     * Update a connection (e.g. record a refreshed token, mark errored) —
     * owner only.
     */
    public function update(User $loggedInUser, User $requestedUser, ExternalAccountConnection $connection): bool
    {
        return $loggedInUser->id === $requestedUser->id
            && $connection->user_id === $requestedUser->id;
    }

    /**
     * Disconnect — owner only.
     */
    public function delete(User $loggedInUser, User $requestedUser, ExternalAccountConnection $connection): bool
    {
        return $loggedInUser->id === $requestedUser->id
            && $connection->user_id === $requestedUser->id;
    }
}
