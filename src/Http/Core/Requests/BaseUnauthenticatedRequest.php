<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * Class BaseUnauthenticatedRequest
 */
abstract class BaseUnauthenticatedRequest extends BaseRequestAbstract
{
    /**
     * Whether or not the current user is authenticated to run this request
     *
     * @throws AuthorizationException
     */
    public function authorize(): bool
    {
        $this->authorizeExpands();

        return true;
    }
}
