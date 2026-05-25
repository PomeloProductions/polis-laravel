<?php

declare(strict_types=1);

namespace Polis\Policies\Subscription;

use App\Models\Subscription\MembershipPlan;
use App\Models\User\User;
use Polis\Contracts\Policies\BasePolicyContract;
use Polis\Policies\BasePolicyAbstract;

abstract class MembershipPlanPolicyAbstract extends BasePolicyAbstract implements BasePolicyContract
{
    /**
     * @return bool
     */
    public function all(User $user)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function view(User $user, MembershipPlan $membershipPlan)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function create(User $user)
    {
        return false;
    }

    /**
     * @return bool
     */
    public function update(User $user, MembershipPlan $membershipPlan)
    {
        return false;
    }

    /**
     * @return bool
     */
    public function delete(User $user, MembershipPlan $membershipPlan)
    {
        return false;
    }
}
