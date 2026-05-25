<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use Illuminate\Database\Eloquent\Model;
use Polis\Contracts\Repositories\User\InvitationTokenRepositoryContract;
use Polis\Contracts\Services\TokenGenerationServiceContract;
use Polis\Models\User\InvitationToken;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class InvitationTokenRepository
 */
class InvitationTokenRepository extends BaseRepositoryAbstract implements InvitationTokenRepositoryContract
{
    private TokenGenerationServiceContract $tokenGenerationService;

    /**
     * InvitationTokenRepository constructor.
     */
    public function __construct(InvitationToken $model, LogContract $log,
        TokenGenerationServiceContract $tokenGenerationService)
    {
        parent::__construct($model, $log);
        $this->tokenGenerationService = $tokenGenerationService;
    }

    /**
     * Finds an invitation token by its token string
     *
     * @return Model|InvitationToken|null
     */
    public function findByToken(string $token): ?InvitationToken
    {
        return $this->model->newQuery()
            ->where('token', '=', $token)
            ->first();
    }

    /**
     * Generates a unique token, or throws an exception if it cannot do so.
     *
     * @throws \OverflowException
     */
    public function generateUniqueToken(): string
    {
        $attempts = 0;
        do {
            $token = $this->tokenGenerationService->generateToken();
            $existingModel = $this->findByToken($token);
            $attempts++;
        } while ($existingModel != null && $attempts < 5);

        if ($existingModel) {
            throw new \OverflowException('Unable to generate unique invitation token.');
        }

        return $token;
    }
}
