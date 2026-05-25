<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\MembershipPlan;

use App\Models\Subscription\MembershipPlan;
use App\Policies\Subscription\MembershipPlanPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoPolicyParameters;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class IndexRequest
 */
class IndexRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoPolicyParameters, HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return MembershipPlanPolicy::ACTION_LIST;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return MembershipPlan::class;
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
