<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\TodoTemplate;

use Polis\Contracts\Policies\BasePolicyContract;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;
use Polis\Models\User\TodoTemplate;

class DeleteRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoRules;

    protected function getPolicyAction(): string
    {
        return BasePolicyContract::ACTION_DELETE;
    }

    protected function getPolicyModel(): string
    {
        return TodoTemplate::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user'), $this->route('template')];
    }
}
