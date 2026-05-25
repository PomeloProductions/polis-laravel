<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use App\Models\User\PasswordToken;
use App\Models\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Polis\Contracts\Repositories\User\PasswordTokenRepositoryContract;
use Polis\Contracts\Services\TokenGenerationServiceContract;
use Polis\Events\User\ForgotPasswordEvent;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Repositories\Traits\NotImplemented\Delete;
use Polis\Repositories\Traits\NotImplemented\Update;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class PasswordTokenRepository
 */
class PasswordTokenRepository extends BaseRepositoryAbstract implements PasswordTokenRepositoryContract
{
    use Delete, \Polis\Repositories\Traits\NotImplemented\FindAll, \Polis\Repositories\Traits\NotImplemented\FindOrFail, Update;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var TokenGenerationServiceContract
     */
    private $tokenGenerationService;

    /**
     * PasswordTokenRepository constructor.
     */
    public function __construct(PasswordToken $model, LogContract $log, Dispatcher $dispatcher,
        TokenGenerationServiceContract $tokenGenerationService)
    {
        parent::__construct($model, $log);
        $this->dispatcher = $dispatcher;
        $this->tokenGenerationService = $tokenGenerationService;
    }

    /**
     * Overrides the parent in order to dispatch the forgot password event
     *
     * @return PasswordToken
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = [])
    {
        /** @var PasswordToken $passwordToken */
        $passwordToken = parent::create($data, $relatedModel, $forcedValues);

        $this->dispatcher->dispatch(new ForgotPasswordEvent($passwordToken));

        return $passwordToken;
    }

    /**
     * Searches for a password token model owned by a user with a token
     *
     * @return Model|PasswordToken|null
     */
    public function findForUser(User $user, string $token): ?PasswordToken
    {
        return $this->model->newQuery()
            ->where('user_id', '=', $user->id)
            ->where('token', '=', $token)
            ->first();
    }

    /**
     * Generates a unique token for a user, or throws an exception if it cannot do so.
     *
     * @throws \OverflowException
     */
    public function generateUniqueToken(User $user): string
    {
        $attempts = 0;
        do {
            $token = $this->tokenGenerationService->generateToken();
            $existingModel = $this->findForUser($user, $token);
            $attempts++;
        } while ($existingModel != null && $attempts < 5);

        if ($existingModel) {
            throw new \OverflowException('Unable to generate unique token for the user.');
        }

        return $token;
    }
}
