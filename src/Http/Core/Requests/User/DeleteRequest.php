<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User;

use App\Models\User\User;
use App\Policies\User\UserPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class DeleteRequest
 */
class DeleteRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return UserPolicy::ACTION_DELETE;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPolicyModel(): string
    {
        return User::class;
    }

    protected function getPolicyParameters(): array
    {
        return [
            $this->route('user'),
        ];
    }
}
