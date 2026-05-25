<?php

declare(strict_types=1);

namespace Polis\Repositories\Vote;

use App\Models\Vote\BallotItemOption;
use Polis\Contracts\Repositories\Vote\BallotItemOptionRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class BallotItemOptionRepository
 */
class BallotItemOptionRepository extends BaseRepositoryAbstract implements BallotItemOptionRepositoryContract
{
    /**
     * BallotItemOptionRepository constructor.
     */
    public function __construct(BallotItemOption $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
