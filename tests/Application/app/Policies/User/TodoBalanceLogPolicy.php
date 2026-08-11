<?php

declare(strict_types=1);

namespace App\Policies\User;

use App\Models\User\TodoBalanceLog;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

class TodoBalanceLogPolicy extends BasePolicyAbstract
{
    public function all(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    public function view(User $loggedInUser, User $requestedUser, TodoBalanceLog $log): bool
    {
        return $loggedInUser->id === $requestedUser->id && $log->user_id === $requestedUser->id;
    }
}
