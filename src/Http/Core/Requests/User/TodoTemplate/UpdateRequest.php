<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\TodoTemplate;

use Polis\Contracts\Policies\BasePolicyContract;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Models\User\TodoTemplate;

class UpdateRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    protected function getPolicyAction(): string
    {
        return BasePolicyContract::ACTION_UPDATE;
    }

    protected function getPolicyModel(): string
    {
        return TodoTemplate::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user'), $this->route('template')];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(TodoTemplate $model): array
    {
        return $model->getValidationRules(TodoTemplate::VALIDATION_RULES_UPDATE);
    }
}
