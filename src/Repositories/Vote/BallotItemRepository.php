<?php

declare(strict_types=1);

namespace Polis\Repositories\Vote;

use App\Models\Vote\BallotItem;
use Polis\Contracts\Repositories\Vote\BallotItemOptionRepositoryContract;
use Polis\Contracts\Repositories\Vote\BallotItemRepositoryContract;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Traits\CanGetAndUnset;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class BallotItemRepository
 */
class BallotItemRepository extends BaseRepositoryAbstract implements BallotItemRepositoryContract
{
    use CanGetAndUnset;

    private BallotItemOptionRepositoryContract $ballotItemOptionRepository;

    /**
     * BallotSubjectRepository constructor.
     */
    public function __construct(BallotItem $model, LogContract $log,
        BallotItemOptionRepositoryContract $ballotItemOptionRepository)
    {
        parent::__construct($model, $log);
        $this->ballotItemOptionRepository = $ballotItemOptionRepository;
    }

    /**
     * Overrides the parent create to syn related models
     *
     * @return BaseModelAbstract
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = [])
    {
        $ballotItemOptions = $this->getAndUnset($data, 'ballot_item_options', []);
        $ballotItem = parent::create($data, $relatedModel, $forcedValues);

        $this->syncChildModels($this->ballotItemOptionRepository, $ballotItem, $ballotItemOptions);

        return $ballotItem;
    }

    /**
     * Makes sure to sync child models properly
     *
     * @param  BallotItem|BaseModelAbstract  $model
     */
    public function update(BaseModelAbstract $model, array $data, array $forcedValues = []): BaseModelAbstract
    {
        $ballotItemOptions = $this->getAndUnset($data, 'ballot_item_options', null);

        if ($ballotItemOptions !== null) {
            $this->syncChildModels($this->ballotItemOptionRepository, $model, $ballotItemOptions, $model->ballotItemOptions);
        }

        return parent::update($model, $data, $forcedValues);
    }
}
