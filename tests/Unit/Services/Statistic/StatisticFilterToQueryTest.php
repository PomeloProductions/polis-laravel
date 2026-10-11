<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Statistic;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\SQLiteConnection;
use PDO;
use Polis\Services\Statistic\StatisticFilterToQuery;
use Polis\Tests\TestCase;

/**
 * Class StatisticFilterToQueryTest
 *
 * Pure (no-DB) coverage of the SQL push-down translator. Each case builds a
 * base query builder backed by an in-memory SQLite connection used ONLY to
 * render SQL (no tables are created and no query is executed), then asserts the
 * translator produces the expected SQL fragment and bindings for every
 * operator, including the `unique` GROUP BY aggregate.
 */
class StatisticFilterToQueryTest extends TestCase
{
    private StatisticFilterToQuery $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = new StatisticFilterToQuery;
    }

    private function newBuilder(): Builder
    {
        $connection = new SQLiteConnection(new PDO('sqlite::memory:'));

        return $connection->query()->from('votes');
    }

    /**
     * @param  array{field: string, operator: string, value: mixed}  $attributes
     */
    private function filter(array $attributes): object
    {
        return (object) $attributes;
    }

    public function test_equals_operator_maps_to_where(): void
    {
        $query = $this->newBuilder();

        $this->translator->applyFilters($query, [
            $this->filter(['field' => 'platform', 'operator' => '=', 'value' => 'nes']),
        ]);

        $this->assertSame('select * from "votes" where "platform" = ?', $query->toSql());
        $this->assertSame(['nes'], $query->getBindings());
    }

    public function test_all_comparison_operators_map_to_where(): void
    {
        foreach (['!=', '>', '>=', '<', '<='] as $operator) {
            $query = $this->newBuilder();

            $this->translator->applyFilters($query, [
                $this->filter(['field' => 'score', 'operator' => $operator, 'value' => '100']),
            ]);

            $this->assertSame(
                sprintf('select * from "votes" where "score" %s ?', $operator),
                $query->toSql()
            );
            $this->assertSame(['100'], $query->getBindings());
        }
    }

    public function test_in_operator_maps_to_where_in_split_on_commas(): void
    {
        $query = $this->newBuilder();

        $this->translator->applyFilters($query, [
            $this->filter(['field' => 'platform', 'operator' => 'in', 'value' => 'nes,snes,n64']),
        ]);

        $this->assertSame('select * from "votes" where "platform" in (?, ?, ?)', $query->toSql());
        $this->assertSame(['nes', 'snes', 'n64'], $query->getBindings());
    }

    public function test_not_in_operator_maps_to_where_not_in_split_on_commas(): void
    {
        $query = $this->newBuilder();

        $this->translator->applyFilters($query, [
            $this->filter(['field' => 'platform', 'operator' => 'not in', 'value' => 'nes,snes']),
        ]);

        $this->assertSame('select * from "votes" where "platform" not in (?, ?)', $query->toSql());
        $this->assertSame(['nes', 'snes'], $query->getBindings());
    }

    public function test_like_operator_wraps_value_in_wildcards(): void
    {
        $query = $this->newBuilder();

        $this->translator->applyFilters($query, [
            $this->filter(['field' => 'name', 'operator' => 'like', 'value' => 'mario']),
        ]);

        $this->assertSame('select * from "votes" where "name" like ?', $query->toSql());
        $this->assertSame(['%mario%'], $query->getBindings());
    }

    public function test_not_like_operator_wraps_value_in_wildcards(): void
    {
        $query = $this->newBuilder();

        $this->translator->applyFilters($query, [
            $this->filter(['field' => 'name', 'operator' => 'not like', 'value' => 'mario']),
        ]);

        $this->assertSame('select * from "votes" where "name" not like ?', $query->toSql());
        $this->assertSame(['%mario%'], $query->getBindings());
    }

    public function test_multiple_filters_are_combined_with_and(): void
    {
        $query = $this->newBuilder();

        $this->translator->applyFilters($query, [
            $this->filter(['field' => 'score', 'operator' => '>=', 'value' => '50']),
            $this->filter(['field' => 'platform', 'operator' => 'in', 'value' => 'nes,snes']),
            $this->filter(['field' => 'name', 'operator' => 'like', 'value' => 'zelda']),
        ]);

        $this->assertSame(
            'select * from "votes" where "score" >= ? and "platform" in (?, ?) and "name" like ?',
            $query->toSql()
        );
        $this->assertSame(['50', 'nes', 'snes', '%zelda%'], $query->getBindings());
    }

    public function test_unique_operator_is_skipped_as_a_row_filter(): void
    {
        $query = $this->newBuilder();

        $this->translator->applyFilters($query, [
            $this->filter(['field' => 'platform', 'operator' => 'unique', 'value' => null]),
        ]);

        // A `unique` row describes a grouping dimension, not a WHERE clause.
        $this->assertSame('select * from "votes"', $query->toSql());
        $this->assertSame([], $query->getBindings());
    }

    public function test_build_aggregate_query_groups_by_field_with_count(): void
    {
        $query = $this->newBuilder();

        $aggregateQuery = $this->translator->buildAggregateQuery($query, 'platform');

        $this->assertSame(
            'select "platform", COUNT(*) as aggregate_count from "votes" group by "platform"',
            $aggregateQuery->toSql()
        );
    }

    public function test_build_aggregate_query_preserves_applied_filters(): void
    {
        $query = $this->newBuilder();

        // Filter first (e.g. the non-unique constraints), then group.
        $this->translator->applyFilters($query, [
            $this->filter(['field' => 'score', 'operator' => '>', 'value' => '0']),
        ]);
        $aggregateQuery = $this->translator->buildAggregateQuery($query, 'platform');

        $this->assertSame(
            'select "platform", COUNT(*) as aggregate_count from "votes" where "score" > ? group by "platform"',
            $aggregateQuery->toSql()
        );
        $this->assertSame(['0'], $aggregateQuery->getBindings());
    }
}
