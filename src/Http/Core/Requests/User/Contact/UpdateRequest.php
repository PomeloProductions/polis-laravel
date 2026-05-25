<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\Contact;

use App\Models\User\Contact;
use App\Policies\User\ContactPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return ContactPolicy::ACTION_UPDATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Contact::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('user'),
            $this->route('contact'),
        ];
    }

    /**
     * The rules for this request
     */
    public function rules(Contact $model)
    {
        return $model->getValidationRules(Contact::VALIDATION_RULES_UPDATE);
    }
}
