<?php

declare(strict_types=1);

namespace Polis\Policies\Vote;

use App\Models\User\User;
use App\Models\Vote\Ballot;
use Polis\Policies\BasePolicyAbstract;

abstract class BallotPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function view(User $user, Ballot $ballot)
    {
        return true;
    }
}
