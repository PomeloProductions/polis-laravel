<?php

declare(strict_types=1);

namespace Polis\Repositories\Vote;

use App\Models\Vote\Vote;
use Polis\Contracts\Repositories\Vote\VoteRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class VoteRepository
 */
class VoteRepository extends BaseRepositoryAbstract implements VoteRepositoryContract
{
    /**
     * VoteRepository constructor.
     */
    public function __construct(Vote $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
