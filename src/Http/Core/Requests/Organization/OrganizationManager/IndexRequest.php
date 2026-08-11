<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Organization\OrganizationManager;

use App\Models\Organization\OrganizationManager;
use App\Policies\Organization\OrganizationManagerPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class IndexRequest
 */
class IndexRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoRules;

    /**
     * All expands that are allowed for this request
     *
     * The dashboard lists organization managers with `expand[user]=*` to show
     * each manager's user details in one call; without allowing it here
     * authorizeExpands() throws an AuthorizationException (403).
     */
    public function allowedExpands(): array
    {
        return ['user'];
    }

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return OrganizationManagerPolicy::ACTION_LIST;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return OrganizationManager::class;
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
