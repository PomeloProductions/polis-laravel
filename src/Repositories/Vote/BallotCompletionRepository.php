<?php

declare(strict_types=1);

namespace Polis\Repositories\Vote;

use App\Models\Vote\BallotCompletion;
use Polis\Contracts\Repositories\Vote\BallotCompletionRepositoryContract;
use Polis\Contracts\Repositories\Vote\VoteRepositoryContract;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Repositories\Traits\NotImplemented\Update;
use Polis\Traits\CanGetAndUnset;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class BallotCompletionRepository
 */
class BallotCompletionRepository extends BaseRepositoryAbstract implements BallotCompletionRepositoryContract
{
    use CanGetAndUnset, Update;

    private VoteRepositoryContract $voteRepository;

    /**
     * BallotCompletionRepository constructor.
     */
    public function __construct(BallotCompletion $model, LogContract $log,
        VoteRepositoryContract $voteRepository)
    {
        parent::__construct($model, $log);
        $this->voteRepository = $voteRepository;
    }

    /**
     * overrides parent to sync votes
     *
     * @return BaseModelAbstract
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = [])
    {
        $votes = $this->getAndUnset($data, 'votes', []);

        $model = parent::create($data, $relatedModel, $forcedValues);

        $this->syncChildModels($this->voteRepository, $model, $votes);

        return $model;
    }
}
