<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\UserPage;

use App\Models\User\UserPage;
use App\Policies\User\UserPagePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

class DeleteRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoRules;

    protected function getPolicyAction(): string
    {
        return UserPagePolicy::ACTION_DELETE;
    }

    protected function getPolicyModel(): string
    {
        return UserPage::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user'), $this->route('page')];
    }
}
