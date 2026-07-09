<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\TodoTemplate;

use Polis\Contracts\Policies\BasePolicyContract;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoRules;
use Polis\Models\User\TodoTemplate;

class IndexRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoRules;

    public function allowedExpands(): array
    {
        return [];
    }

    protected function getPolicyAction(): string
    {
        return BasePolicyContract::ACTION_LIST;
    }

    protected function getPolicyModel(): string
    {
        return TodoTemplate::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user')];
    }
}
