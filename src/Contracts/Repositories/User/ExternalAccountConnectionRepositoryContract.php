<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\User;

use App\Models\User\User;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\BaseRepositoryContract;
use Polis\Models\User\ExternalAccountConnection;

/**
 * Interface ExternalAccountConnectionRepositoryContract
 */
interface ExternalAccountConnectionRepositoryContract extends BaseRepositoryContract
{
    /**
     * Find the connection for a (user, provider) pair, or null if the user
     * has never linked that provider.
     *
     * The (user_id, provider) pair is the migration's unique key so this
     * method returns at most one row.
     */
    public function findForUserAndProvider(User $user, string $provider): ?ExternalAccountConnection;

    /**
     * Return every connection for a user across all providers. Useful for
     * "linked accounts" settings screens.
     *
     * @return Collection<int, ExternalAccountConnection>
     */
    public function findAllForUser(User $user): Collection;

    /**
     * Return every connection for a provider that has expired or will expire
     * before the given threshold. Drives refresh-token scheduling.
     *
     * Callers pass `now()->addMinutes(10)` to catch tokens about to lapse;
     * passing `now()` returns only already-expired rows.
     *
     * @return Collection<int, ExternalAccountConnection>
     */
    public function findExpiringByProvider(string $provider, \DateTimeInterface $before): Collection;
}
