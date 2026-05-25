<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\User;

use App\Models\User\User;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\BaseRepositoryContract;

/**
 * Interface UserRepositoryContract
 */
interface UserRepositoryContract extends BaseRepositoryContract
{
    /**
     * Attempts to look up a user by email address, and returns null if we cannot find one
     */
    public function findByEmail(string $email): ?User;

    /**
     * Finds all system users in the system
     *
     * Creates a new user if one is not found
     */
    public function findSuperAdmins(): Collection;
}
