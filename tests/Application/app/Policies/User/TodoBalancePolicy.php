<?php

declare(strict_types=1);

namespace App\Policies\User;

use App\Models\User\TodoBalance;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

class TodoBalancePolicy extends BasePolicyAbstract
{
    public function all(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    public function view(User $loggedInUser, User $requestedUser, TodoBalance $balance): bool
    {
        return $loggedInUser->id === $requestedUser->id && $balance->user_id === $requestedUser->id;
    }

    public function create(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    public function update(User $loggedInUser, User $requestedUser, TodoBalance $balance): bool
    {
        return $loggedInUser->id === $requestedUser->id && $balance->user_id === $requestedUser->id;
    }

    public function delete(User $loggedInUser, User $requestedUser, TodoBalance $balance): bool
    {
        return $loggedInUser->id === $requestedUser->id && $balance->user_id === $requestedUser->id;
    }
}
