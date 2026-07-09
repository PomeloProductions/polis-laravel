<?php

declare(strict_types=1);

namespace Polis\Support;

use InvalidArgumentException;
use Polis\Contracts\CarryForwardContract;

/**
 * Applies {@see CarryForwardContract} rules to numeric values when a
 * period-based surface is copied forward into the next period.
 *
 * This is the domain-agnostic extraction of the Todo module's copy-value
 * logic. It knows nothing about tasks, tallies or hours — it just answers
 * "given a value, a rule and a step, what is the value in the next period?"
 */
final class CopyRuleSet implements CarryForwardContract
{
    /**
     * Apply a carry-forward rule to a numeric value.
     *
     * - increment: value + step
     * - preserve:  value unchanged
     * - reset:     0
     * - omit:      0 (the field is expected to be dropped structurally by the
     *              caller; we return 0 so a stray call is still well-defined)
     *
     * @param  int|float  $value  The current value.
     * @param  string  $rule  One of the CarryForwardContract::RULE_* constants.
     * @param  int|float  $step  The increment step (only used by RULE_INCREMENT).
     */
    public function apply(int|float $value, string $rule, int|float $step = 1): int|float
    {
        return match ($rule) {
            self::RULE_INCREMENT => $value + $step,
            self::RULE_PRESERVE => $value,
            self::RULE_RESET, self::RULE_OMIT => 0,
            default => throw new InvalidArgumentException("Unknown carry-forward rule [{$rule}]."),
        };
    }

    /**
     * Whether a rule identifier is one of the known rules.
     */
    public function isValidRule(string $rule): bool
    {
        return in_array($rule, self::ALL_RULES, true);
    }

    /**
     * Whether a field carrying this rule should be dropped from the copy
     * entirely rather than transformed.
     */
    public function shouldOmit(string $rule): bool
    {
        return $rule === self::RULE_OMIT;
    }
}
