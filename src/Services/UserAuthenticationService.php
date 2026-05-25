<?php

declare(strict_types=1);

namespace Polis\Services;

use App\Models\User\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Exceptions\AuthenticationException;

/**
 * Class UserAuthenticationService
 */
class UserAuthenticationService extends EloquentUserProvider implements UserProvider
{
    /**
     * UserAuthenticationService constructor.
     */
    public function __construct(Hasher $hasher, private UserRepositoryContract $userRepository)
    {
        parent::__construct($hasher, new User);
        $this->hasher = $hasher;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        try {
            return $this->userRepository->findOrFail($identifier);
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (! empty($credentials['email'])) {
            return $this->userRepository->findByEmail($credentials['email']);
        }

        throw new AuthenticationException('No valid identifying credential.');
    }

    /**
     * Validate a user against the given credentials.
     *
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return $this->hasher->check($credentials['password'], $user->getAuthPassword());
    }
}
