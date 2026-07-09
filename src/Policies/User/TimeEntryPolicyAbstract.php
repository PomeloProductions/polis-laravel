<?php

declare(strict_types=1);

namespace Polis\Policies\User;

use App\Models\User\User;
use Polis\Models\User\TimeEntry;
use Polis\Policies\BasePolicyAbstract;

/**
 * Abstract policy for TimeEntry (timer + time-entry actions authorize against
 * this). Consumers extend it as App\Policies\User\TimeEntryPolicy.
 */
abstract class TimeEntryPolicyAbstract extends BasePolicyAbstract
{
    public function all(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    public function view(User $loggedInUser, User $requestedUser, TimeEntry $timeEntry): bool
    {
        return $loggedInUser->id === $requestedUser->id && $timeEntry->user_id === $requestedUser->id;
    }

    public function create(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    public function update(User $loggedInUser, User $requestedUser, TimeEntry $timeEntry): bool
    {
        return $loggedInUser->id === $requestedUser->id && $timeEntry->user_id === $requestedUser->id;
    }

    public function delete(User $loggedInUser, User $requestedUser, TimeEntry $timeEntry): bool
    {
        return $loggedInUser->id === $requestedUser->id && $timeEntry->user_id === $requestedUser->id;
    }
}
