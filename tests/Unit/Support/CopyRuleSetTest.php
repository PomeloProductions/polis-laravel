<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Support;

use InvalidArgumentException;
use Polis\Contracts\CarryForwardContract;
use Polis\Support\CopyRuleSet;
use Polis\Tests\TestCase;

final class CopyRuleSetTest extends TestCase
{
    public function test_increment_adds_step(): void
    {
        $rules = new CopyRuleSet;

        $this->assertEquals(6, $rules->apply(5, CarryForwardContract::RULE_INCREMENT));
        $this->assertEquals(8, $rules->apply(5, CarryForwardContract::RULE_INCREMENT, 3));
        $this->assertEqualsWithDelta(5.5, $rules->apply(5.0, CarryForwardContract::RULE_INCREMENT, 0.5), 0.0001);
    }

    public function test_preserve_keeps_value(): void
    {
        $rules = new CopyRuleSet;

        $this->assertEquals(5, $rules->apply(5, CarryForwardContract::RULE_PRESERVE));
        $this->assertEquals(5, $rules->apply(5, CarryForwardContract::RULE_PRESERVE, 99));
    }

    public function test_reset_and_omit_return_zero(): void
    {
        $rules = new CopyRuleSet;

        $this->assertEquals(0, $rules->apply(5, CarryForwardContract::RULE_RESET));
        $this->assertEquals(0, $rules->apply(5, CarryForwardContract::RULE_OMIT));
    }

    public function test_unknown_rule_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CopyRuleSet)->apply(1, 'nonsense');
    }

    public function test_is_valid_rule(): void
    {
        $rules = new CopyRuleSet;

        $this->assertTrue($rules->isValidRule(CarryForwardContract::RULE_INCREMENT));
        $this->assertTrue($rules->isValidRule(CarryForwardContract::RULE_OMIT));
        $this->assertFalse($rules->isValidRule('nonsense'));
    }

    public function test_should_omit(): void
    {
        $rules = new CopyRuleSet;

        $this->assertTrue($rules->shouldOmit(CarryForwardContract::RULE_OMIT));
        $this->assertFalse($rules->shouldOmit(CarryForwardContract::RULE_RESET));
    }
}
