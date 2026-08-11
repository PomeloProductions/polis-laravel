<?php

declare(strict_types=1);

namespace App\Http\Core\Requests\User\TodoTemplate;

use App\Models\User\TodoTemplate;
use App\Policies\User\TodoTemplatePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoRules;

class IndexRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoRules;

    public function allowedExpands(): array
    {
        return [];
    }

    protected function getPolicyAction(): string
    {
        return TodoTemplatePolicy::ACTION_LIST;
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
