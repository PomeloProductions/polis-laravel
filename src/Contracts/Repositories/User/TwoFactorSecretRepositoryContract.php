<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\User;

use App\Models\User\User;
use Polis\Contracts\Repositories\BaseRepositoryContract;
use Polis\Models\User\TwoFactorSecret;

/**
 * Interface TwoFactorSecretRepositoryContract
 */
interface TwoFactorSecretRepositoryContract extends BaseRepositoryContract
{
    /**
     * Find the (single) two-factor secret for a user, or null if the user has
     * never enrolled. The migration enforces a unique index on `user_id` so
     * this returns at most one row.
     */
    public function findForUser(User $user): ?TwoFactorSecret;

    /**
     * Find the CONFIRMED two-factor secret for a user, or null when the user
     * has no secret or only a pending (unconfirmed) one. The login flow uses
     * this to decide whether a second factor is required: a pending secret
     * must never cause a challenge.
     */
    public function findConfirmedForUser(User $user): ?TwoFactorSecret;
}
