<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Messaging\PushTemplate;

use App\Models\Organization\Organization;
use App\Policies\Messaging\PushTemplatePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class ViewRequest
 *
 * Shows a single push template (resolved + default copy) by string key
 * within the route's organization scope.
 */
class ViewRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;
    use HasNoRules;

    protected function getPolicyAction(): string
    {
        return PushTemplatePolicy::ACTION_VIEW;
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
