<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Entity\Subscription;

use App\Models\Subscription\Subscription;
use App\Policies\Subscription\SubscriptionPolicy;
use Polis\Contracts\Http\HasEntityInRequestContract;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Entity\Traits\IsEntityRequestTrait;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class IndexRequest
 */
class IndexRequest extends BaseAuthenticatedRequestAbstract implements HasEntityInRequestContract
{
    use HasNoRules, IsEntityRequestTrait;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return SubscriptionPolicy::ACTION_LIST;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Subscription::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->getEntity(),
        ];
    }

    /**
     * @return array|string[]
     */
    public function allowedExpands(): array
    {
        return [
            'membershipPlanRate',
            'membershipPlanRate.membershipPlan',
        ];
    }
}
