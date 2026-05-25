<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\ForgotPassword;

use Illuminate\Validation\Rule;
use Polis\Http\Core\Requests\BaseUnauthenticatedRequest;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class ResetPasswordRequest
 */
class ResetPasswordRequest extends BaseUnauthenticatedRequest
{
    use HasNoExpands;

    /**
     * get the validation rules for resetting a password
     */
    public function rules(): array
    {
        return [
            'email' => 'required|max:120|email|'.Rule::exists('users', 'email'),
            'token' => 'required|max:40|'.
                Rule::exists('password_tokens', 'token').
                '|user_owns_token|token_is_not_expired',
            'password' => 'required|max:120',
        ];
    }
}
