<?php

declare(strict_types=1);

namespace App\Http\Core\Requests\User\TodoTemplate;

use App\Models\User\TodoTemplate;
use App\Policies\User\TodoTemplatePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

class DeleteRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoRules;

    protected function getPolicyAction(): string
    {
        return TodoTemplatePolicy::ACTION_DELETE;
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
