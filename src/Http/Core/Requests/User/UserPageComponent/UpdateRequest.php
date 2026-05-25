<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\UserPageComponent;

use App\Models\User\UserPageComponent;
use App\Policies\User\UserPageComponentPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

class UpdateRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    protected function getPolicyAction(): string
    {
        return UserPageComponentPolicy::ACTION_UPDATE;
    }

    protected function getPolicyModel(): string
    {
        return UserPageComponent::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user'), $this->route('page'), $this->route('component')];
    }

    public function rules(UserPageComponent $model): array
    {
        return $model->getValidationRules(UserPageComponent::VALIDATION_RULES_UPDATE);
    }
}
