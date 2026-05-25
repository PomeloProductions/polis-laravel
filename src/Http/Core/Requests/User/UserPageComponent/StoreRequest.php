<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\UserPageComponent;

use App\Models\User\UserPageComponent;
use App\Policies\User\UserPageComponentPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

class StoreRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    protected function getPolicyAction(): string
    {
        return UserPageComponentPolicy::ACTION_CREATE;
    }

    protected function getPolicyModel(): string
    {
        return UserPageComponent::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user'), $this->route('page')];
    }

    public function rules(UserPageComponent $model): array
    {
        return $model->getValidationRules(UserPageComponent::VALIDATION_RULES_CREATE);
    }
}
