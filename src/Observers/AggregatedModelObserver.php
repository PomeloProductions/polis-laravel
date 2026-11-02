<?php

declare(strict_types=1);

namespace Polis\Observers;

use App\Models\Statistic\Statistic;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Polis\Contracts\Models\CanBeAggregatedContract;
use Polis\Contracts\Models\CanBeStatisticTargetContract;
use Polis\Contracts\Services\Relations\RelationTraversalServiceContract;
use Polis\Jobs\Statistic\MaintainStatisticCellsJob;
use Polis\Jobs\Statistic\ProcessTargetStatisticsJob;
use Polis\Support\Statistic\StatisticDefinition;

class AggregatedModelObserver
{
    public function __construct(
        private readonly RelationTraversalServiceContract $relationTraversalService,
        private readonly Dispatcher $dispatcher
    ) {}

    /**
     * Handle the Model "created" event.
     */
    public function created(CanBeAggregatedContract $model): void
    {
        $this->dispatchStatisticProcessing($model);
        $this->dispatchCellMaintenance($model);
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(CanBeAggregatedContract $model): void
    {
        $this->dispatchStatisticProcessing($model);
        // On update capture any PREVIOUS primary-target ids so a row that moved
        // owners settles its old scope too (tuple-delta on both sides).
        $this->dispatchCellMaintenance($model, $this->previousPrimaryTargetIds($model));
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(CanBeAggregatedContract $model): void
    {
        $this->dispatchStatisticProcessing($model);
        $this->dispatchCellMaintenance($model);
    }

    /**
     * Handle the Model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->dispatchStatisticProcessing($model);

        if ($model instanceof CanBeAggregatedContract) {
            $this->dispatchCellMaintenance($model);
        }
    }

    /**
     * Dispatches statistic processing for models that can be aggregated
     * (the legacy, single-dimension in-PHP path).
     */
    private function dispatchStatisticProcessing(CanBeAggregatedContract $model): void
    {
        foreach ($model->getStatisticTargetRelationPath() as $relationPath) {
            $targetModels = $this->relationTraversalService->traverseRelations($model, $relationPath);

            foreach ($targetModels as $targetModel) {
                if ($targetModel instanceof CanBeStatisticTargetContract) {
                    $this->dispatcher->dispatch(new ProcessTargetStatisticsJob($targetModel));
                }
            }
        }
    }

    /**
     * Dispatch the incremental tuple-delta maintenance job for engine-backed
     * (multi-dimensional) statistics — the changed row IS a base-table row, so a
     * bounded per-target recompute keeps its cells current.
     *
     * @param  array<string, int|string|null>  $previousTargetIds
     */
    private function dispatchCellMaintenance(Model $model, array $previousTargetIds = []): void
    {
        $this->dispatcher->dispatch(new MaintainStatisticCellsJob($model, $previousTargetIds));
    }

    /**
     * The row's previous primary-target id per engine-backed statistic whose
     * base model is this row's class, read from the dirty original attributes.
     *
     * @return array<string, int|string|null>
     */
    private function previousPrimaryTargetIds(Model $model): array
    {
        $previous = [];

        foreach (Statistic::query()->whereNotNull('measure')->get() as $statistic) {
            $definition = StatisticDefinition::for($statistic);

            if (! $definition->usesAggregationEngine()) {
                continue;
            }

            $primaryTarget = $definition->primaryTarget();
            $modelClass = Relation::getMorphedModel($statistic->model);

            if ($primaryTarget === null || $modelClass === null || ! $model instanceof $modelClass) {
                continue;
            }

            $column = $primaryTarget['id_column'];

            if ($model->isDirty($column)) {
                $previous[(string) $statistic->id] = $model->getOriginal($column);
            }
        }

        return $previous;
    }
}
