<?php

declare(strict_types=1);

namespace Polis\Support\Statistic;

use InvalidArgumentException;

/**
 * Class DerivedExpressionEvaluator
 *
 * Pure evaluator for the (deliberately tiny) derived-statistic expression
 * grammar. A derived statistic's value for a cell is computed from the values of
 * its operand statistics' MATCHING cells (same dimension-tuple). Only three
 * operations are supported, by design:
 *
 *   - ratio(numerator, denominator)  -> numerator / denominator
 *   - difference(a, b)               -> a - b
 *   - sum(a, b, ...)                 -> a + b + ...
 *
 * Correctness/safety rules (enforced here):
 *   - A null/missing operand means the cell cannot be computed -> returns null
 *     (the derived cell stores null rather than erroring), EXCEPT that `sum`
 *     treats a missing operand as the additive identity is NOT done — a missing
 *     operand is ambiguous, so sum also returns null if any operand is missing.
 *   - ratio by a zero (or null) denominator returns null (never divides by
 *     zero, never throws).
 *
 * The evaluator is intentionally stateless and DB-free so it can be unit-tested
 * exhaustively and reused by both the sync service and the recompute job.
 *
 * NON-ADDITIVE CAVEAT: a value returned here (a ratio in particular) must NEVER
 * be summed/rolled-up across a dimension. A rolled-up ratio must be re-derived
 * from rolled-up operands. Materializing the point value is fine; aggregating
 * the materialized ratio is not. See the DerivedStatisticService docblock.
 */
final class DerivedExpressionEvaluator
{
    public const OP_RATIO = 'ratio';

    public const OP_DIFFERENCE = 'difference';

    public const OP_SUM = 'sum';

    public const OPERATIONS = [self::OP_RATIO, self::OP_DIFFERENCE, self::OP_SUM];

    /**
     * Evaluate a derived expression for one cell.
     *
     * @param  string  $operation  One of ratio|difference|sum.
     * @param  array<int, float|int|null>  $operands  The operand cell values, in
     *                                                declaration order. Missing operand
     *                                                cells must be passed as null.
     * @return float|null The computed value, or null when it cannot be safely
     *                    computed (missing operand / zero denominator).
     */
    public function evaluate(string $operation, array $operands): ?float
    {
        if (! in_array($operation, self::OPERATIONS, true)) {
            throw new InvalidArgumentException("Unsupported derived operation [{$operation}].");
        }

        return match ($operation) {
            self::OP_RATIO => $this->ratio($operands),
            self::OP_DIFFERENCE => $this->difference($operands),
            self::OP_SUM => $this->sum($operands),
        };
    }

    /**
     * @param  array<int, float|int|null>  $operands
     */
    private function ratio(array $operands): ?float
    {
        if (count($operands) !== 2) {
            throw new InvalidArgumentException('ratio() requires exactly 2 operands.');
        }

        [$numerator, $denominator] = $operands;

        if ($numerator === null || $denominator === null) {
            return null;
        }

        // Zero-denominator-safe: store null rather than error or ±INF.
        if ((float) $denominator === 0.0) {
            return null;
        }

        return (float) $numerator / (float) $denominator;
    }

    /**
     * @param  array<int, float|int|null>  $operands
     */
    private function difference(array $operands): ?float
    {
        if (count($operands) !== 2) {
            throw new InvalidArgumentException('difference() requires exactly 2 operands.');
        }

        [$a, $b] = $operands;

        if ($a === null || $b === null) {
            return null;
        }

        return (float) $a - (float) $b;
    }

    /**
     * @param  array<int, float|int|null>  $operands
     */
    private function sum(array $operands): ?float
    {
        if ($operands === []) {
            throw new InvalidArgumentException('sum() requires at least 1 operand.');
        }

        $total = 0.0;

        foreach ($operands as $operand) {
            // A missing operand is ambiguous for a sum; refuse rather than
            // silently treat it as 0 and report a wrong total.
            if ($operand === null) {
                return null;
            }

            $total += (float) $operand;
        }

        return $total;
    }
}
