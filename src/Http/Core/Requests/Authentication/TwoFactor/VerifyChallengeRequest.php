<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Authentication\TwoFactor;

use Polis\Http\Core\Requests\BaseUnauthenticatedRequest;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class VerifyChallengeRequest
 *
 * The second step of a 2FA login. Unauthenticated: the caller has passed the
 * password check (step one returned a short-lived challenge_token) but has no
 * JWT yet. Carries the challenge token plus the TOTP or recovery code.
 */
class VerifyChallengeRequest extends BaseUnauthenticatedRequest
{
    use HasNoExpands;

    public function rules(): array
    {
        return [
            'challenge_token' => 'required|string',
            'code' => 'required|string|max:16',
        ];
    }
}
