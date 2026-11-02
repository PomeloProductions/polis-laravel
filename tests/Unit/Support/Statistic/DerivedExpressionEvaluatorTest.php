<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Support\Statistic;

use InvalidArgumentException;
use Polis\Support\Statistic\DerivedExpressionEvaluator;
use Polis\Tests\TestCase;

/**
 * Class DerivedExpressionEvaluatorTest
 *
 * Unit coverage for the derived-expression evaluator: ratio/difference/sum,
 * zero-denominator safety, and missing-operand handling.
 */
final class DerivedExpressionEvaluatorTest extends TestCase
{
    private DerivedExpressionEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new DerivedExpressionEvaluator;
    }

    public function test_ratio_divides(): void
    {
        $this->assertEqualsWithDelta(0.75, $this->evaluator->evaluate('ratio', [3, 4]), 0.0001);
    }

    public function test_ratio_by_zero_returns_null(): void
    {
        $this->assertNull($this->evaluator->evaluate('ratio', [3, 0]));
    }

    public function test_ratio_with_missing_operand_returns_null(): void
    {
        $this->assertNull($this->evaluator->evaluate('ratio', [3, null]));
        $this->assertNull($this->evaluator->evaluate('ratio', [null, 4]));
    }

    public function test_difference_subtracts(): void
    {
        $this->assertEquals(1.0, $this->evaluator->evaluate('difference', [5, 4]));
        $this->assertEquals(-2.0, $this->evaluator->evaluate('difference', [3, 5]));
    }

    public function test_difference_with_missing_operand_returns_null(): void
    {
        $this->assertNull($this->evaluator->evaluate('difference', [5, null]));
    }

    public function test_sum_adds_all_operands(): void
    {
        $this->assertEquals(9.0, $this->evaluator->evaluate('sum', [2, 3, 4]));
    }

    public function test_sum_with_missing_operand_returns_null(): void
    {
        $this->assertNull($this->evaluator->evaluate('sum', [2, null, 4]));
    }

    public function test_unknown_operation_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->evaluator->evaluate('product', [2, 3]);
    }

    public function test_ratio_requires_two_operands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->evaluator->evaluate('ratio', [1, 2, 3]);
    }

    public function test_sum_requires_at_least_one_operand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->evaluator->evaluate('sum', []);
    }
}
