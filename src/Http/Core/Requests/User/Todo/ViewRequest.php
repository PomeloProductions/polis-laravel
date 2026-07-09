<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\Todo;

use Polis\Contracts\Policies\BasePolicyContract;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;
use Polis\Models\User\TodoSetting;

class ViewRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoRules;

    protected function getPolicyAction(): string
    {
        return BasePolicyContract::ACTION_LIST;
    }

    protected function getPolicyModel(): string
    {
        return TodoSetting::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user')];
    }
}
