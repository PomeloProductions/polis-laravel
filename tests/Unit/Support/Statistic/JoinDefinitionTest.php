<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Support\Statistic;

use InvalidArgumentException;
use Polis\Support\Statistic\JoinDefinition;
use Polis\Tests\TestCase;

/**
 * Class JoinDefinitionTest
 *
 * Unit coverage for declarative-join parsing: defaults (type=inner, guard=true,
 * on_extra=[]), alias fallback, on_extra normalization, type validation, and the
 * required-field guards.
 */
final class JoinDefinitionTest extends TestCase
{
    public function test_parses_a_minimal_join_with_defaults(): void
    {
        $join = JoinDefinition::fromArray([
            'table' => 'release_dates',
            'alias' => 'rd',
            'left' => 'base.id',
            'right' => 'rd.release_id',
        ]);

        $this->assertSame('release_dates', $join->table());
        $this->assertSame('rd', $join->alias());
        $this->assertSame('release_dates as rd', $join->tableWithAlias());
        $this->assertSame('base.id', $join->left());
        $this->assertSame('rd.release_id', $join->right());
        $this->assertSame(JoinDefinition::TYPE_INNER, $join->type());
        $this->assertFalse($join->isLeft());
        $this->assertTrue($join->guardsSoftDeletes());
        $this->assertSame([], $join->onExtra());
    }

    public function test_alias_defaults_to_table_name_when_omitted(): void
    {
        $join = JoinDefinition::fromArray([
            'table' => 'collections',
            'left' => 'ci.collection_id',
            'right' => 'collections.id',
        ]);

        $this->assertSame('collections', $join->alias());
        $this->assertSame('collections as collections', $join->tableWithAlias());
    }

    public function test_parses_left_join_and_disabled_guard(): void
    {
        $join = JoinDefinition::fromArray([
            'table' => 'release_dates',
            'alias' => 'rd',
            'left' => 'base.id',
            'right' => 'rd.release_id',
            'type' => 'left',
            'guard_soft_deletes' => false,
        ]);

        $this->assertTrue($join->isLeft());
        $this->assertSame(JoinDefinition::TYPE_LEFT, $join->type());
        $this->assertFalse($join->guardsSoftDeletes());
    }

    public function test_type_is_case_insensitive(): void
    {
        $join = JoinDefinition::fromArray([
            'table' => 't', 'left' => 'base.id', 'right' => 't.id', 'type' => 'LEFT',
        ]);

        $this->assertTrue($join->isLeft());
    }

    public function test_parses_nested_on_extra_conditions(): void
    {
        $join = JoinDefinition::fromArray([
            'table' => 'collection_items',
            'alias' => 'ci',
            'left' => 'base.id',
            'right' => 'ci.item_id',
            'on_extra' => [
                ['ci.item_type', '=', 'release'],
                ['ci.active', '=', 1],
            ],
        ]);

        $this->assertSame([
            ['ci.item_type', '=', 'release'],
            ['ci.active', '=', 1],
        ], $join->onExtra());
    }

    public function test_normalizes_a_single_un_nested_on_extra_triple(): void
    {
        $join = JoinDefinition::fromArray([
            'table' => 'collection_items',
            'alias' => 'ci',
            'left' => 'base.id',
            'right' => 'ci.item_id',
            'on_extra' => ['ci.item_type', '=', 'release'],
        ]);

        $this->assertSame([['ci.item_type', '=', 'release']], $join->onExtra());
    }

    public function test_rejects_an_unsupported_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        JoinDefinition::fromArray([
            'table' => 't', 'left' => 'base.id', 'right' => 't.id', 'type' => 'cross',
        ]);
    }

    public function test_requires_a_table(): void
    {
        $this->expectException(InvalidArgumentException::class);

        JoinDefinition::fromArray(['left' => 'base.id', 'right' => 't.id']);
    }

    public function test_requires_left_and_right(): void
    {
        $this->expectException(InvalidArgumentException::class);

        JoinDefinition::fromArray(['table' => 't', 'alias' => 't']);
    }

    public function test_rejects_a_malformed_on_extra_condition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        JoinDefinition::fromArray([
            'table' => 't', 'left' => 'base.id', 'right' => 't.id',
            'on_extra' => [['only', 'two']],
        ]);
    }

    public function test_parses_a_table_raw_sub_select_join(): void
    {
        $sql = 'SELECT release_id, YEAR(MIN(release_date)) AS earliest_year '
            .'FROM release_dates WHERE release_date IS NOT NULL AND deleted_at IS NULL '
            .'GROUP BY release_id';

        $join = JoinDefinition::fromArray([
            'table_raw' => $sql,
            'alias' => 'ey',
            'left' => 'base.id',
            'right' => 'ey.release_id',
        ]);

        $this->assertTrue($join->isSubSelect());
        $this->assertNull($join->table());
        $this->assertSame($sql, $join->tableRaw());
        $this->assertSame('ey', $join->alias());
        $this->assertSame('base.id', $join->left());
        $this->assertSame('ey.release_id', $join->right());
        // Defaults still apply for a sub-select join.
        $this->assertSame(JoinDefinition::TYPE_INNER, $join->type());
        $this->assertTrue($join->guardsSoftDeletes());
    }

    public function test_physical_table_join_is_not_a_sub_select(): void
    {
        $join = JoinDefinition::fromArray([
            'table' => 'release_dates', 'alias' => 'rd',
            'left' => 'base.id', 'right' => 'rd.release_id',
        ]);

        $this->assertFalse($join->isSubSelect());
        $this->assertNull($join->tableRaw());
        $this->assertSame('release_dates', $join->table());
    }

    public function test_rejects_both_table_and_table_raw(): void
    {
        $this->expectException(InvalidArgumentException::class);

        JoinDefinition::fromArray([
            'table' => 'release_dates',
            'table_raw' => 'SELECT release_id FROM release_dates GROUP BY release_id',
            'alias' => 'rd',
            'left' => 'base.id',
            'right' => 'rd.release_id',
        ]);
    }

    public function test_rejects_neither_table_nor_table_raw(): void
    {
        $this->expectException(InvalidArgumentException::class);

        JoinDefinition::fromArray([
            'alias' => 'rd', 'left' => 'base.id', 'right' => 'rd.release_id',
        ]);
    }

    public function test_table_raw_join_requires_an_alias(): void
    {
        $this->expectException(InvalidArgumentException::class);

        JoinDefinition::fromArray([
            'table_raw' => 'SELECT release_id FROM release_dates GROUP BY release_id',
            'left' => 'base.id',
            'right' => 'x.release_id',
        ]);
    }
}
