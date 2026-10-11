<?php

declare(strict_types=1);

namespace Polis\Services\Statistic;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Class StatisticFilterToQuery
 *
 * Pure, reusable translator that converts a statistic's `statistic_filters`
 * rows into query-builder constraints so filtering + counting can be pushed
 * down to SQL. It operates ONLY on a passed-in query builder and the filter
 * rows; it contains no model-specific SQL and performs no I/O of its own
 * beyond invoking the builder it is handed.
 *
 * Operator mapping (kept 1:1 with the in-memory path in
 * {@see TargetStatisticProcessingService}):
 *   =, !=, >, >=, <, <=  -> where(field, op, value)
 *   in / not in          -> whereIn / whereNotIn(field, explode(',', value))
 *   like / not like      -> where(field, 'like' / 'not like', '%value%')
 *   unique               -> handled separately via {@see self::aggregate()} as a GROUP BY
 *
 * A filter row is any object exposing `->field`, `->operator` and `->value`
 * (e.g. an App\Models\Statistic\StatisticFilter). This keeps the translator
 * decoupled from the Eloquent model so it can be unit-tested with no database.
 */
class StatisticFilterToQuery
{
    /**
     * Comparison operators that map directly onto a `where` clause.
     */
    private const COMPARISON_OPERATORS = ['=', '!=', '>', '>=', '<', '<='];

    /**
     * Apply every non-`unique` filter row as a constraint on the query.
     *
     * The `unique` operator is intentionally skipped here; it describes a
     * grouping dimension, not a row filter, and is consumed by
     * {@see self::aggregate()}.
     *
     * @param  Builder  $query  The query builder to constrain (mutated in place and returned).
     * @param  iterable<object{field: string, operator: string, value: mixed}>  $filters
     */
    public function applyFilters(Builder $query, iterable $filters): Builder
    {
        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter->field, $filter->operator, $filter->value);
        }

        return $query;
    }

    /**
     * Compute the aggregate for the (already filtered) query.
     *
     * When a `unique` filter is supplied the query is grouped by that filter's
     * field and a per-value COUNT(*) is returned, keyed by the distinct value —
     * mirroring {@see TargetStatisticProcessingService}'s processUniqueResults()
     * output exactly (one entry per distinct value, including NULL). Otherwise a
     * single `['total' => COUNT(*)]` is returned.
     *
     * @param  Builder  $query  A builder already constrained via {@see self::applyFilters()}.
     * @param  object{field: string, operator: string, value: mixed}|null  $uniqueFilter
     * @return array<array-key, int>
     */
    public function aggregate(Builder $query, ?object $uniqueFilter = null): array
    {
        if ($uniqueFilter === null) {
            return ['total' => $query->count()];
        }

        $field = $uniqueFilter->field;
        $rows = $this->buildAggregateQuery($this->toQueryBuilder($query), $field)->get();

        $result = [];

        foreach ($rows as $row) {
            $result[$row->{$field}] = (int) $row->aggregate_count;
        }

        return $result;
    }

    /**
     * Build the GROUP BY query that produces a per-value COUNT(*) for the
     * unique field. Pure builder shaping with no execution, so the resulting
     * SQL/bindings are independently assertable.
     */
    public function buildAggregateQuery(QueryBuilder $query, string $field): QueryBuilder
    {
        return $query
            ->select($field)
            ->selectRaw('COUNT(*) as aggregate_count')
            ->groupBy($field);
    }

    /**
     * Resolve the underlying base query builder, accepting either an Eloquent
     * builder (the production path) or a base query builder directly.
     */
    private function toQueryBuilder(Builder $query): QueryBuilder
    {
        if ($query instanceof EloquentBuilder) {
            return $query->getQuery();
        }

        /** @var QueryBuilder $query */
        return $query;
    }

    /**
     * Apply a single filter row to the query, translating the operator to SQL.
     */
    private function applyFilter(Builder $query, string $field, string $operator, mixed $value): void
    {
        if (in_array($operator, self::COMPARISON_OPERATORS, true)) {
            $query->where($field, $operator, $value);

            return;
        }

        switch ($operator) {
            case 'in':
                $query->whereIn($field, $this->splitList($value));
                break;
            case 'not in':
                $query->whereNotIn($field, $this->splitList($value));
                break;
            case 'like':
                $query->where($field, 'like', '%'.$value.'%');
                break;
            case 'not like':
                $query->where($field, 'not like', '%'.$value.'%');
                break;
            case 'unique':
                // Grouping dimension, not a row filter — handled in aggregate().
                break;
        }
    }

    /**
     * Split a comma-delimited filter value into a list, mirroring the
     * in-memory path's explode(',', $value) for the in / not in operators.
     *
     * @return array<int, string>
     */
    private function splitList(mixed $value): array
    {
        return explode(',', (string) $value);
    }
}
