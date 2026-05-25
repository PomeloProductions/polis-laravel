<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\UserPageComponent;

use App\Models\User\UserPageComponent;
use App\Policies\User\UserPageComponentPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

class DeleteRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoRules;

    protected function getPolicyAction(): string
    {
        return UserPageComponentPolicy::ACTION_DELETE;
    }

    protected function getPolicyModel(): string
    {
        return UserPageComponent::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user'), $this->route('page'), $this->route('component')];
    }
}
