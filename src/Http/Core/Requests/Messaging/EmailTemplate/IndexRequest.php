<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Messaging\EmailTemplate;

use App\Models\Organization\Organization;
use App\Policies\Messaging\EmailTemplatePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class IndexRequest
 *
 * Lists every email template (DB rows + in-code defaults merged) for the
 * route's organization. The route is expected to be scoped to an
 * organization (e.g. `organizations/{organization}/email-templates`) so
 * the policy can gate access against that org's managers.
 */
class IndexRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;
    use HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return EmailTemplatePolicy::ACTION_LIST;
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
