<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Authentication\TwoFactor;

use App\Models\User\User;
use App\Policies\User\UserPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoPolicyParameters;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class EnrollRequest
 *
 * Begins TOTP enrollment for the authenticated user. Acts on self only (the
 * controller reads auth()->user()), so it reuses the same self-scoped policy
 * gate as MeRequest and carries no body.
 */
class EnrollRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoPolicyParameters, HasNoRules;

    protected function getPolicyAction(): string
    {
        return UserPolicy::ACTION_VIEW_SELF;
    }

    protected function getPolicyModel(): string
    {
        return User::class;
    }
}
