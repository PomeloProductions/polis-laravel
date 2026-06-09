<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use App\Models\User\User;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\User\ExternalAccountConnectionRepositoryContract;
use Polis\Models\User\ExternalAccountConnection;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class ExternalAccountConnectionRepository
 */
class ExternalAccountConnectionRepository extends BaseRepositoryAbstract implements ExternalAccountConnectionRepositoryContract
{
    public function __construct(ExternalAccountConnection $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }

    public function findForUserAndProvider(User $user, string $provider): ?ExternalAccountConnection
    {
        return $this->model->newQuery()
            ->where('user_id', '=', $user->id)
            ->where('provider', '=', $provider)
            ->first();
    }

    public function findAllForUser(User $user): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', '=', $user->id)
            ->orderBy('provider')
            ->get();
    }

    public function findExpiringByProvider(string $provider, \DateTimeInterface $before): Collection
    {
        return $this->model->newQuery()
            ->where('provider', '=', $provider)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', $before)
            ->where('status', '=', ExternalAccountConnection::STATUS_CONNECTED)
            ->get();
    }
}
