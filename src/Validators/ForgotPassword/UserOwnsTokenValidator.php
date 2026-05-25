<?php

declare(strict_types=1);

namespace Polis\Validators\ForgotPassword;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Polis\Contracts\Repositories\User\PasswordTokenRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Validators\BaseValidatorAbstract;

/**
 * Class UserOwnsTokenValidator
 */
class UserOwnsTokenValidator extends BaseValidatorAbstract
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var UserRepositoryContract
     */
    private $userRepository;

    /**
     * @var PasswordTokenRepositoryContract
     */
    private $passwordTokenRepository;

    /**
     * UserOwnsTokenValidator constructor.
     */
    public function __construct(Request $request, UserRepositoryContract $userRepository,
        PasswordTokenRepositoryContract $passwordTokenRepository)
    {
        $this->request = $request;
        $this->userRepository = $userRepository;
        $this->passwordTokenRepository = $passwordTokenRepository;
    }

    /**
     * Responds to 'user_owns_token', and must be attached to the token field
     *
     * @param  array  $parameters
     * @return bool
     */
    public function validate($attribute, $value, $parameters = [], ?Validator $validator = null)
    {
        $this->ensureValidatorAttribute('token', $attribute);

        $email = $this->request->input('email', null);

        if (! $email) {
            return false;
        }

        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            return false;
        }

        $passwordToken = $this->passwordTokenRepository->findForUser($user, $value);

        return $passwordToken != null;
    }
}
