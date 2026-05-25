<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\User;

use App\Models\User\PasswordToken;
use App\Models\User\User;
use Polis\Contracts\Repositories\BaseRepositoryContract;

/**
 * Interface PasswordTokenRepositoryContract
 */
interface PasswordTokenRepositoryContract extends BaseRepositoryContract
{
    /**
     * Generates a unique token for a user, or throws an exception if it cannot do so.
     *
     * @throws \OverflowException
     */
    public function generateUniqueToken(User $user): string;

    /**
     * Searches for a password token model owned by a user with a token
     */
    public function findForUser(User $user, string $token): ?PasswordToken;
}
