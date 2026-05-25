<?php

declare(strict_types=1);

namespace Polis\Repositories\Vote;

use App\Models\Vote\Ballot;
use Polis\Contracts\Repositories\Vote\BallotItemRepositoryContract;
use Polis\Contracts\Repositories\Vote\BallotRepositoryContract;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Traits\CanGetAndUnset;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class BallotRepository
 */
class BallotRepository extends BaseRepositoryAbstract implements BallotRepositoryContract
{
    use CanGetAndUnset;

    /**
     * @var BallotItemRepositoryContract
     */
    private $ballotSubjectRepository;

    /**
     * BallotRepository constructor.
     */
    public function __construct(Ballot $model, LogContract $log,
        BallotItemRepositoryContract $ballotSubjectRepository)
    {
        parent::__construct($model, $log);
        $this->ballotSubjectRepository = $ballotSubjectRepository;
    }

    /**
     * overrides the parent in order to create all related ballot subjects
     *
     * @return BaseModelAbstract
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = [])
    {
        $ballotItems = $this->getAndUnset($data, 'ballot_items', []);
        $model = parent::create($data, $relatedModel, $forcedValues);

        $this->syncChildModels($this->ballotSubjectRepository, $model, $ballotItems);

        return $model;
    }

    /**
     * Makes sure to sync child models properly
     *
     * @param  Ballot|BaseModelAbstract  $model
     */
    public function update(BaseModelAbstract $model, array $data, array $forcedValues = []): BaseModelAbstract
    {
        $ballotItems = $this->getAndUnset($data, 'ballot_items', null);

        if ($ballotItems !== null) {
            $this->syncChildModels($this->ballotSubjectRepository, $model, $ballotItems, $model->ballotItems);
        }

        return parent::update($model, $data, $forcedValues);
    }
}
