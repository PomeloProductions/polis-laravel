<?php

declare(strict_types=1);

namespace App\Policies\User;

use App\Models\User\ActiveTimer;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

class ActiveTimerPolicy extends BasePolicyAbstract
{
    public function all(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    public function view(User $loggedInUser, User $requestedUser, ActiveTimer $timer): bool
    {
        return $loggedInUser->id === $requestedUser->id && $timer->user_id === $requestedUser->id;
    }

    public function create(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    public function update(User $loggedInUser, User $requestedUser, ActiveTimer $timer): bool
    {
        return $loggedInUser->id === $requestedUser->id && $timer->user_id === $requestedUser->id;
    }

    public function delete(User $loggedInUser, User $requestedUser, ActiveTimer $timer): bool
    {
        return $loggedInUser->id === $requestedUser->id && $timer->user_id === $requestedUser->id;
    }
}
