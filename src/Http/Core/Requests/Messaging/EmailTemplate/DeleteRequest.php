<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Messaging\EmailTemplate;

use App\Models\Organization\Organization;
use App\Policies\Messaging\EmailTemplatePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class DeleteRequest
 *
 * Reverts an organization's override back to the next layer in the
 * lookup hierarchy (global row -> in-code default) by deleting the
 * org-scoped EmailTemplate row. Has no effect on the global default
 * or the in-code default.
 */
class DeleteRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;
    use HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return EmailTemplatePolicy::ACTION_DELETE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Organization::class;
    }

    protected function getPolicyParameters(): array
    {
        return [
            $this->route('organization'),
        ];
    }
}
