<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Messaging\EmailTemplate;

use App\Models\Organization\Organization;
use App\Policies\Messaging\EmailTemplatePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class UpdateRequest
 *
 * Upserts the org-scoped (or global, for super admins) EmailTemplate row
 * for the given `key`. The request body carries a non-empty `subject` and
 * `body_html`. The controller is responsible for performing the upsert
 * against the appropriate organization_id.
 */
class UpdateRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return EmailTemplatePolicy::ACTION_UPDATE;
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
     * admins can be authorized against the right tenant.
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('organization'),
        ];
    }

    /**
     * Validation rules.
     *
     * - subject is required and non-empty
     * - body_html is required and non-empty (the underlying email body)
     * - key on the route is what determines which template is being
     *   upserted; it's not in the payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
        ];
    }
}
