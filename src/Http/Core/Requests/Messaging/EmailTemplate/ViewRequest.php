<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Messaging\EmailTemplate;

use App\Models\Organization\Organization;
use App\Policies\Messaging\EmailTemplatePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class ViewRequest
 *
 * Shows a single email template (resolved + default copy) by string key
 * within the route's organization scope.
 */
class ViewRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;
    use HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return EmailTemplatePolicy::ACTION_VIEW;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Organization::class;
    }

    /**
     * The Organization route binding is passed to the policy so org
     * managers can be authorized against the right tenant.
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('organization'),
        ];
    }
}
