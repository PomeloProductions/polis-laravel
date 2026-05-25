<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class BaseRequestAbstract
 */
abstract class BaseAuthenticatedRequestAbstract extends BaseRequestAbstract
{
    use AuthorizesRequests {
        AuthorizesRequests::authorize as authorizeRequest;
    }

    /**
     * Get the policy action for the guard
     */
    abstract protected function getPolicyAction(): string;

    /**
     * Get the class name of the policy that this request utilizes
     */
    abstract protected function getPolicyModel(): string;

    /**
     * Gets any additional parameters needed for the policy function
     */
    abstract protected function getPolicyParameters(): array;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @throws AuthorizationException
     */
    public function authorize(): bool
    {
        $this->authorizeExpands();
        $parameters = array_merge([$this->getPolicyModel()], $this->getPolicyParameters());
        $this->authorizeRequest($this->getPolicyAction(), $parameters);

        return true;
    }
}
