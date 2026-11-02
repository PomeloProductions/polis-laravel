<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Support\Statistic;

use Polis\Support\Statistic\DimensionTuple;
use Polis\Support\Statistic\DimensionValue;
use Polis\Tests\TestCase;

/**
 * Class DimensionTupleTest
 *
 * Unit coverage for the dimension_key canonicalizer: axis-order independence,
 * self-cross pair canonicalization, scalar/entity token distinctness, and
 * stability.
 */
final class DimensionTupleTest extends TestCase
{
    public function test_empty_tuple_canonicalizes_to_empty_string(): void
    {
        $this->assertSame('', (new DimensionTuple([]))->key());
    }

    public function test_key_is_independent_of_axis_declaration_order(): void
    {
        $a = new DimensionTuple([
            'voting_type' => DimensionValue::scalar('best'),
            'category' => DimensionValue::scalar('arcade'),
        ]);

        $b = new DimensionTuple([
            'category' => DimensionValue::scalar('arcade'),
            'voting_type' => DimensionValue::scalar('best'),
        ]);

        $this->assertSame($a->key(), $b->key());
    }

    public function test_different_values_produce_different_keys(): void
    {
        $a = new DimensionTuple(['x' => DimensionValue::scalar('best')]);
        $b = new DimensionTuple(['x' => DimensionValue::scalar('worst')]);

        $this->assertNotSame($a->key(), $b->key());
    }

    public function test_entity_and_scalar_tokens_do_not_collide(): void
    {
        $entity = new DimensionTuple(['x' => DimensionValue::entity('platform', 7)]);
        $scalar = new DimensionTuple(['x' => DimensionValue::scalar('7')]);

        $this->assertNotSame($entity->key(), $scalar->key());
    }

    public function test_unordered_pair_collapses_to_one_key(): void
    {
        $ab = (new DimensionTuple([
            'platform_a' => DimensionValue::entity('platform', 1),
            'platform_b' => DimensionValue::entity('platform', 2),
        ], [['platform_a', 'platform_b']]))->key();

        $ba = (new DimensionTuple([
            'platform_a' => DimensionValue::entity('platform', 2),
            'platform_b' => DimensionValue::entity('platform', 1),
        ], [['platform_a', 'platform_b']]))->key();

        $this->assertSame($ab, $ba);
    }

    public function test_pair_canonicalization_orders_ids_numerically(): void
    {
        // 2 < 10 numerically — a lexical sort would wrongly put "10" before "2".
        $canonical = (new DimensionTuple([
            'platform_a' => DimensionValue::entity('platform', 10),
            'platform_b' => DimensionValue::entity('platform', 2),
        ], [['platform_a', 'platform_b']]))->canonicalized();

        $values = $canonical->values();
        $this->assertSame('2', (string) $values['platform_a']->valueId());
        $this->assertSame('10', (string) $values['platform_b']->valueId());
    }

    public function test_distinct_pairs_remain_distinct(): void
    {
        $pair12 = (new DimensionTuple([
            'a' => DimensionValue::entity('platform', 1),
            'b' => DimensionValue::entity('platform', 2),
        ], [['a', 'b']]))->key();

        $pair34 = (new DimensionTuple([
            'a' => DimensionValue::entity('platform', 3),
            'b' => DimensionValue::entity('platform', 4),
        ], [['a', 'b']]))->key();

        $this->assertNotSame($pair12, $pair34);
    }

    public function test_key_is_stable_across_calls(): void
    {
        $tuple = new DimensionTuple([
            'voting_type' => DimensionValue::scalar('best'),
            'platform_a' => DimensionValue::entity('platform', 1),
            'platform_b' => DimensionValue::entity('platform', 2),
        ], [['platform_a', 'platform_b']]);

        $this->assertSame($tuple->key(), $tuple->key());
    }

    public function test_null_scalar_is_stable_and_non_colliding(): void
    {
        $null = new DimensionTuple(['x' => DimensionValue::scalar(null)]);
        $empty = new DimensionTuple(['x' => DimensionValue::scalar('')]);

        // A null scalar normalizes to '' — same token by design (both "no value").
        $this->assertSame($null->key(), $empty->key());
        // But distinct from a populated value.
        $populated = new DimensionTuple(['x' => DimensionValue::scalar('v')]);
        $this->assertNotSame($null->key(), $populated->key());
    }
}
