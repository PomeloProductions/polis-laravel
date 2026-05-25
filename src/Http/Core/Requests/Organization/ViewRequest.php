<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Organization;

use App\Models\Organization\Organization;
use App\Policies\Organization\OrganizationPolicy;
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
        return OrganizationPolicy::ACTION_VIEW;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Organization::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('organization'),
        ];
    }

    /**
     * All allowed expands for this request
     */
    public function allowedExpands(): array
    {
        return [
            'paymentMethods',
            'subscriptions',
            'subscriptions.membershipPlanRate',
            'subscriptions.membershipPlanRate.membershipPlan',
            'subscriptions.membershipPlanRate.membershipPlan.features',
            'subscriptions.paymentMethod',
        ];
    }
}
