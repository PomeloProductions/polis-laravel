<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\User;

use Polis\Contracts\Repositories\BaseRepositoryContract;
use Polis\Models\User\InvitationToken;

/**
 * Interface InvitationTokenRepositoryContract
 */
interface InvitationTokenRepositoryContract extends BaseRepositoryContract
{
    /**
     * Generates a unique token, or throws an exception if it cannot do so.
     *
     * @throws \OverflowException
     */
    public function generateUniqueToken(): string;

    /**
     * Finds an invitation token by its token string
     */
    public function findByToken(string $token): ?InvitationToken;
}
