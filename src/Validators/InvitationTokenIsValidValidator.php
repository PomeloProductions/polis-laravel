<?php

declare(strict_types=1);

namespace Polis\Validators;

use Illuminate\Contracts\Validation\Validator;
use Polis\Contracts\Repositories\User\InvitationTokenRepositoryContract;

/**
 * Class InvitationTokenIsValidValidator
 */
class InvitationTokenIsValidValidator
{
    /**
     * The key for easy reference around the app
     */
    const KEY = 'invitation_token_is_valid';

    private InvitationTokenRepositoryContract $invitationTokenRepository;

    /**
     * InvitationTokenIsValidValidator constructor.
     */
    public function __construct(InvitationTokenRepositoryContract $invitationTokenRepository)
    {
        $this->invitationTokenRepository = $invitationTokenRepository;
    }

    /**
     * This is invoked by the validator rule 'invitation_token_is_valid'
     *
     * @param  array  $parameters
     */
    public function validate($attribute, $value, $parameters = [], ?Validator $validator = null): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $invitationToken = $this->invitationTokenRepository->findByToken($value);

        if (! $invitationToken) {
            return false;
        }

        if ($invitationToken->isUsed()) {
            return false;
        }

        return true;
    }
}
