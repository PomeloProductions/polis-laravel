<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Organization\OrganizationManager;

use App\Models\Organization\OrganizationManager;
use App\Policies\Organization\OrganizationManagerPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class StoreRequest
 */
class StoreRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return OrganizationManagerPolicy::ACTION_CREATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return OrganizationManager::class;
    }

    /**
     * @return array
     */
    public function rules(OrganizationManager $organizationManager)
    {
        return $organizationManager->getValidationRules(OrganizationManager::VALIDATION_RULES_CREATE);
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
}
