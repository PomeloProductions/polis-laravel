<?php

declare(strict_types=1);

namespace App\Http\Core\Requests\User\Todo;

use App\Models\User\TodoSetting;
use App\Policies\User\TodoSettingPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

class UpdateSettingsRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    protected function getPolicyAction(): string
    {
        return TodoSettingPolicy::ACTION_CREATE;
    }

    protected function getPolicyModel(): string
    {
        return TodoSetting::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user')];
    }

    public function rules(TodoSetting $model): array
    {
        return $model->getValidationRules(TodoSetting::VALIDATION_RULES_UPDATE);
    }
}
