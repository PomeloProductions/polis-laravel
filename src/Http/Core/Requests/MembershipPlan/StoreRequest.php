<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\MembershipPlan;

use App\Models\Subscription\MembershipPlan;
use App\Policies\Subscription\MembershipPlanPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoPolicyParameters;

/**
 * Class StoreRequest
 */
class StoreRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoPolicyParameters;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return MembershipPlanPolicy::ACTION_CREATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return MembershipPlan::class;
    }

    /**
     * @return array
     */
    public function rules(MembershipPlan $membershipPlan)
    {
        return $membershipPlan->getValidationRules(MembershipPlan::VALIDATION_RULES_CREATE);
    }
}
