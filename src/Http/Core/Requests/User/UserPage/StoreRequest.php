<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\UserPage;

use App\Models\User\UserPage;
use App\Policies\User\UserPagePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

class StoreRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    protected function getPolicyAction(): string
    {
        return UserPagePolicy::ACTION_CREATE;
    }

    protected function getPolicyModel(): string
    {
        return UserPage::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user')];
    }

    public function rules(UserPage $model): array
    {
        return $model->getValidationRules(UserPage::VALIDATION_RULES_CREATE);
    }
}
