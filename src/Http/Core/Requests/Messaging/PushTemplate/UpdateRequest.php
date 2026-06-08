<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Messaging\PushTemplate;

use App\Models\Organization\Organization;
use App\Policies\Messaging\PushTemplatePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class UpdateRequest
 *
 * Upserts the org-scoped (or global, for super admins) PushTemplate row
 * for the given `key`. The request body carries a non-empty `title` and
 * plain-text `body`. The controller is responsible for performing the
 * upsert against the appropriate organization_id.
 */
class UpdateRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    protected function getPolicyAction(): string
    {
        return PushTemplatePolicy::ACTION_UPDATE;
    }

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

    /**
     * Validation rules.
     *
     * - title is required and non-empty (push notification headline)
     * - body is required and non-empty (push notification message, plain text)
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ];
    }
}
