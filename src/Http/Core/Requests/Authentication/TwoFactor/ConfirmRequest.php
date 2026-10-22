<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Authentication\TwoFactor;

use App\Models\User\User;
use App\Policies\User\UserPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoPolicyParameters;

/**
 * Class ConfirmRequest
 *
 * Confirms TOTP enrollment with a code from the authenticated user's
 * authenticator app. Acts on self only.
 */
class ConfirmRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoPolicyParameters;

    protected function getPolicyAction(): string
    {
        return UserPolicy::ACTION_VIEW_SELF;
    }

    protected function getPolicyModel(): string
    {
        return User::class;
    }

    /**
     * TOTP codes are 6 digits; recovery codes are not accepted here (confirm
     * must prove possession of the authenticator app itself).
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:16',
        ];
    }
}
