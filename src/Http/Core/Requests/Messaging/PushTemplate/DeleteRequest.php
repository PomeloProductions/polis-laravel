<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Messaging\PushTemplate;

use App\Models\Organization\Organization;
use App\Policies\Messaging\PushTemplatePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class DeleteRequest
 *
 * Reverts an organization's override back to the next layer in the
 * lookup hierarchy (global row -> in-code default) by deleting the
 * org-scoped PushTemplate row.
 */
class DeleteRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;
    use HasNoRules;

    protected function getPolicyAction(): string
    {
        return PushTemplatePolicy::ACTION_DELETE;
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
}
