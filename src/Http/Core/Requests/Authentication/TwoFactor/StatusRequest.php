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
 * Class StatusRequest
 *
 * Reports whether the authenticated user has 2FA enabled. Acts on self only.
 */
class StatusRequest extends BaseAuthenticatedRequestAbstract
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
