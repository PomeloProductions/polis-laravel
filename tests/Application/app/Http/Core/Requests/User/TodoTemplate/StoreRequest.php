<?php

declare(strict_types=1);

namespace App\Http\Core\Requests\User\TodoTemplate;

use App\Models\User\TodoTemplate;
use App\Policies\User\TodoTemplatePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

class StoreRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    protected function getPolicyAction(): string
    {
        return TodoTemplatePolicy::ACTION_CREATE;
    }

    protected function getPolicyModel(): string
    {
        return TodoTemplate::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user')];
    }

    public function rules(TodoTemplate $model): array
    {
        return $model->getValidationRules(TodoTemplate::VALIDATION_RULES_CREATE);
    }
}
