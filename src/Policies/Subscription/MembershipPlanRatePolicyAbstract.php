<?php

declare(strict_types=1);

namespace Polis\Policies\Subscription;

use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

abstract class MembershipPlanRatePolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $user)
    {
        return false;
    }
}
