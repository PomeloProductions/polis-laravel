<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\ForgotPassword;

use Illuminate\Validation\Rule;
use Polis\Http\Core\Requests\BaseUnauthenticatedRequest;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class ForgotPasswordRequest
 */
class ForgotPasswordRequest extends BaseUnauthenticatedRequest
{
    use HasNoExpands;

    /**
     * get the validation rules for when someone has forgotten their password, and needs a token sent to them
     */
    public function rules(): array
    {
        return [
            'email' => 'required|max:120|email|'.Rule::exists('users', 'email'),
        ];
    }
}
