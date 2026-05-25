<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User;

use App\Models\User\User;
use App\Policies\User\UserPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoPolicyParameters;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class MeRequest
 */
class MeRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoPolicyParameters, HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return UserPolicy::ACTION_VIEW_SELF;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return User::class;
    }

    /**
     * All expands that are allowed for this request
     */
    public function allowedExpands(): array
    {
        return [
            'organizationManagers',
            'organizationManagers.organization',
            'paymentMethods',
            'roles',
            'subscriptions',
            'subscriptions.membershipPlanRate',
            'subscriptions.membershipPlanRate.membershipPlan',
            'subscriptions.membershipPlanRate.membershipPlan.features',
            'subscriptions.paymentMethod',
        ];
    }
}
