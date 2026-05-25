<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Authentication;

use Polis\Http\Core\Requests\BaseUnauthenticatedRequest;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class LoginRequest
 */
class LoginRequest extends BaseUnauthenticatedRequest
{
    use HasNoExpands;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => 'required|max:256|email',
            'password' => 'required|max:256',
        ];
    }
}
