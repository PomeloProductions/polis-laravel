<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Organization;

use App\Models\Organization\Organization;
use App\Policies\Organization\OrganizationPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoPolicyParameters;
use Polis\Http\Core\Requests\Traits\RejectsUnknownParams;

/**
 * Class StoreRequest
 */
class StoreRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoPolicyParameters, RejectsUnknownParams;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return OrganizationPolicy::ACTION_CREATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Organization::class;
    }

    /**
     * @return array
     */
    public function rules(Organization $organization)
    {
        return $organization->getValidationRules(Organization::VALIDATION_RULES_CREATE);
    }
}
