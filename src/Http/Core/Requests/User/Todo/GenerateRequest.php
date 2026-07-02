<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\Todo;

use Polis\Contracts\Policies\BasePolicyContract;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Models\User\TodoSetting;

class GenerateRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    protected function getPolicyAction(): string
    {
        return BasePolicyContract::ACTION_CREATE;
    }

    protected function getPolicyModel(): string
    {
        return TodoSetting::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user')];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'through_date' => ['required', 'date', 'date_format:Y-m-d'],
        ];
    }
}
