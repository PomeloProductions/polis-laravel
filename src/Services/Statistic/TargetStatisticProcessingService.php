<?php

declare(strict_types=1);

namespace Polis\Services\Statistic;

use App\Models\Statistic\StatisticFilter;
use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Collection;
use Polis\Contracts\Repositories\Statistic\TargetStatisticRepositoryContract;
use Polis\Contracts\Services\Relations\RelationTraversalServiceContract;
use Polis\Contracts\Services\Statistic\TargetStatisticProcessingServiceContract;

/**
 * Class TargetStatisticProcessingService
 */
class TargetStatisticProcessingService implements TargetStatisticProcessingServiceContract
{
    public function __construct(
        private readonly RelationTraversalServiceContract $relationTraversalService,
        private readonly TargetStatisticRepositoryContract $targetStatisticRepository
    ) {}

    /**
     * Processes a target statistic by traversing relations and applying filters
     */
    public function processSingleTargetStatistic(TargetStatistic $targetStatistic): void
    {
        // Get all models at the end of the relation chain
        $models = $this->relationTraversalService->traverseRelations(
            $targetStatistic->target,
            $targetStatistic->statistic->relation
        );

        // Get all filters for this statistic
        $filters = $targetStatistic->statistic->filters;

        // Apply filters to the models
        $filteredModels = $this->applyFilters($models, $filters);

        // Check if any filter requires unique values
        $uniqueFilter = $filters->first(function (StatisticFilter $filter) {
            return $filter->operator === 'unique';
        });

        // Process results based on whether we need unique values or a total count
        $result = $uniqueFilter
            ? $this->processUniqueResults($filteredModels, $uniqueFilter)
            : ['total' => $filteredModels->count()];

        // Update the target statistic through the repository
        $this->targetStatisticRepository->update($targetStatistic, ['result' => $result]);
    }

    /**
     * Applies all filters to the collection of models
     */
    private function applyFilters(Collection $models, Collection $filters): Collection
    {
        return $models->filter(function ($model) use ($filters) {
            foreach ($filters as $filter) {
                if ($filter->operator === 'unique') {
                    continue;
                }

                $fieldValue = data_get($model, $filter->field);
                $filterValue = $filter->value;

                if (! $this->evaluateFilter($fieldValue, $filter->operator, $filterValue)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Evaluates a single filter condition
     *
     * @param  mixed  $fieldValue
     * @param  mixed  $filterValue
     */
    private function evaluateFilter($fieldValue, string $operator, $filterValue): bool
    {
        switch ($operator) {
            case '=':
                return $fieldValue == $filterValue;
            case '!=':
                return $fieldValue != $filterValue;
            case '>':
                return $fieldValue > $filterValue;
            case '>=':
                return $fieldValue >= $filterValue;
            case '<':
                return $fieldValue < $filterValue;
            case '<=':
                return $fieldValue <= $filterValue;
            case 'in':
                return in_array($fieldValue, explode(',', $filterValue));
            case 'not in':
                return ! in_array($fieldValue, explode(',', $filterValue));
            case 'like':
                return str_contains(strtolower($fieldValue), strtolower($filterValue));
            case 'not like':
                return ! str_contains(strtolower($fieldValue), strtolower($filterValue));
            default:
                return false;
        }
    }

    /**
     * Processes results for unique value grouping
     */
    private function processUniqueResults(Collection $models, StatisticFilter $uniqueFilter): array
    {
        $uniqueValues = $models->pluck($uniqueFilter->field)->unique();
        $result = [];

        foreach ($uniqueValues as $value) {
            $result[$value] = $models->where($uniqueFilter->field, $value)->count();
        }

        return $result;
    }
}
