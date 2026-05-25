<?php

declare(strict_types=1);

namespace Polis\Policies\Vote;

use App\Models\User\User;
use App\Models\Vote\Ballot;
use Polis\Policies\BasePolicyAbstract;

abstract class BallotCompletionPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $loggedInUser, User $requestedUser)
    {
        return $loggedInUser->id == $requestedUser->id;
    }

    /**
     * @return bool
     */
    public function create(User $user, Ballot $ballot)
    {
        return true;
    }
}
