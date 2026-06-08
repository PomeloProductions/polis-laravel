<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Messaging\PushTemplate;

use App\Models\Organization\Organization;
use App\Policies\Messaging\PushTemplatePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class IndexRequest
 *
 * Lists every push template (DB rows + in-code defaults merged) for the
 * route's organization. The route is expected to be scoped to an
 * organization (e.g. `organizations/{organization}/push-templates`).
 */
class IndexRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;
    use HasNoRules;

    protected function getPolicyAction(): string
    {
        return PushTemplatePolicy::ACTION_LIST;
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
