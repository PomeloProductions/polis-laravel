<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User;

use App\Models\User\User;
use App\Policies\User\UserPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class IndexRequest
 */
class IndexRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return UserPolicy::ACTION_LIST;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return User::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [];
    }

    /**
     * All expands that are allowed for this request
     */
    public function allowedExpands(): array
    {
        return [
            'roles',
        ];
    }
}
