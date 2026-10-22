<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use App\Models\User\User;
use Polis\Contracts\Repositories\User\TwoFactorSecretRepositoryContract;
use Polis\Models\User\TwoFactorSecret;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class TwoFactorSecretRepository
 */
class TwoFactorSecretRepository extends BaseRepositoryAbstract implements TwoFactorSecretRepositoryContract
{
    public function __construct(TwoFactorSecret $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }

    public function findForUser(User $user): ?TwoFactorSecret
    {
        return $this->model->newQuery()
            ->where('user_id', '=', $user->id)
            ->first();
    }

    public function findConfirmedForUser(User $user): ?TwoFactorSecret
    {
        return $this->model->newQuery()
            ->where('user_id', '=', $user->id)
            ->whereNotNull('confirmed_at')
            ->first();
    }
}
