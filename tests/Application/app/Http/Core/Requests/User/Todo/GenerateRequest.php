<?php

declare(strict_types=1);

namespace App\Http\Core\Requests\User\Todo;

use App\Models\User\TodoSetting;
use App\Policies\User\TodoSettingPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

class GenerateRequest extends BaseAuthenticatedRequestAbstract
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

    public function rules(): array
    {
        return [
            'through_date' => ['required', 'date', 'date_format:Y-m-d'],
        ];
    }
}
