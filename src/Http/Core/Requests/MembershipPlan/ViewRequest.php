<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\MembershipPlan;

use App\Models\Subscription\MembershipPlan;
use App\Policies\Subscription\MembershipPlanPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class ViewRequest
 */
class ViewRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return MembershipPlanPolicy::ACTION_VIEW;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return MembershipPlan::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('membership_plan'),
        ];
    }

    /**
     * All expands that are allowed for this request
     */
    public function allowedExpands(): array
    {
        return [
            'features',
        ];
    }
}
