<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User;

use App\Models\User\User;
use App\Policies\User\UserPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\RejectsUnknownParams;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, RejectsUnknownParams;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return UserPolicy::ACTION_UPDATE;
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
        return [$this->route('user')];
    }

    /**
     * Gets the validation rules needed for this request
     */
    public function rules(User $user): array
    {
        return $user->getValidationRules(User::VALIDATION_RULES_UPDATE, $this->route('user'));
    }
}
