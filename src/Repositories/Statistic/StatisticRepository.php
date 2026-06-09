<?php

declare(strict_types=1);

namespace Polis\Repositories\Statistic;

use App\Models\Statistic\Statistic;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\Statistic\StatisticRepositoryContract;
use Polis\Events\Statistic\StatisticCreatedEvent;
use Polis\Events\Statistic\StatisticDeletedEvent;
use Polis\Events\Statistic\StatisticUpdatedEvent;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Traits\CanGetAndUnset;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class StatisticRepository
 */
class StatisticRepository extends BaseRepositoryAbstract implements StatisticRepositoryContract
{
    use CanGetAndUnset;

    public function __construct(
        Statistic $model,
        LogContract $log,
        private readonly StatisticFilterRepository $statisticFilterRepository,
        private readonly Dispatcher $dispatcher
    ) {
        parent::__construct($model, $log);
    }

    /**
     * {@inheritDoc}
     */
    public function model(): string
    {
        return Statistic::class;
    }

    /**
     * {@inheritDoc}
     */
    public function update(BaseModelAbstract $model, array $data, array $forcedValues = []): BaseModelAbstract
    {
        $statisticFilters = $this->getAndUnset($data, 'statistic_filters');

        $model = parent::update($model, $data, $forcedValues);

        if ($statisticFilters !== null) {
            $this->syncChildModels(
                $this->statisticFilterRepository,
                $model,
                $statisticFilters,
                $model->statisticFilters
            );
        }

        $this->dispatcher->dispatch(new StatisticUpdatedEvent($model));

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = []): BaseModelAbstract
    {
        $statisticFilters = $this->getAndUnset($data, 'statistic_filters') ?? [];

        $model = parent::create($data, $relatedModel, $forcedValues);

        if ($statisticFilters) {
            $this->syncChildModels(
                $this->statisticFilterRepository,
                $model,
                $statisticFilters
            );
        }

        $this->dispatcher->dispatch(new StatisticCreatedEvent($model));

        return $model;
    }

    public function delete(BaseModelAbstract $model): bool
    {
        $result = parent::delete($model);
        $this->dispatcher->dispatch(new StatisticDeletedEvent($model));

        return $result;
    }

    /**
     * Get all statistics for a given model
     */
    public function findAllForModel(string $model): Collection
    {
        return $this->model->newQuery()
            ->where('model', $model)->get();
    }
}
