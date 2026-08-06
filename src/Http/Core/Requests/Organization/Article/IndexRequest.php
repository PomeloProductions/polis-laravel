<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Organization\Article;

use App\Models\Wiki\Article;
use App\Policies\Wiki\OrganizationArticlePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoRules;
use Polis\Policies\Wiki\OrganizationArticlePolicyAbstract;

/**
 * Class IndexRequest
 *
 * Guards `GET /organizations/{organization}/articles` — the org-scoped listing
 * of an organization's contracts (Articles). Authorization runs through
 * {@see OrganizationArticlePolicyAbstract::all()}, which
 * requires the caller to manage the organization in the route (or be a
 * super-admin). The organization is passed to the policy as a parameter, exactly
 * like the OrganizationManager index request.
 */
class IndexRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return OrganizationArticlePolicy::ACTION_LIST;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Article::class;
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
     * All expands that are allowed for this request
     */
    public function allowedExpands(): array
    {
        return [
            'createdBy',
            'iterations',
        ];
    }
}
