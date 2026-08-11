<?php

declare(strict_types=1);

namespace App\Http\Core\Requests\User\Todo;

use App\Models\User\TodoSetting;
use App\Policies\User\TodoSettingPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

class ViewRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoRules;

    protected function getPolicyAction(): string
    {
        return TodoSettingPolicy::ACTION_LIST;
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
