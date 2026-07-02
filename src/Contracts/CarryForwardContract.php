<?php

declare(strict_types=1);

namespace Polis\Contracts;

/**
 * Describes how a single numeric value should carry forward when a period-based
 * page/component is copied into the next period (e.g. yesterday's board rolled
 * into today's).
 *
 * The four canonical rules originated in the Todo module but are domain-agnostic:
 * any recurring surface that needs to decide "what happens to this counter when
 * the period rolls over" can reuse them.
 */
interface CarryForwardContract
{
    /**
     * Add one step (default 1) to the value. Used for tallies that should grow
     * each scheduled period (e.g. "do this N more times").
     */
    public const RULE_INCREMENT = 'increment';

    /**
     * Keep the value exactly as-is across the copy (e.g. lifetime totals).
     */
    public const RULE_PRESERVE = 'preserve';

    /**
     * Reset the value back to zero on copy (e.g. daily counters).
     */
    public const RULE_RESET = 'reset';

    /**
     * Drop the value entirely — the field should not be copied forward at all.
     */
    public const RULE_OMIT = 'omit';

    /**
     * All rules that describe a numeric transformation (RULE_OMIT is a
     * structural rule handled by the caller, not a value transform).
     *
     * @var list<string>
     */
    public const VALUE_RULES = [
        self::RULE_INCREMENT,
        self::RULE_PRESERVE,
        self::RULE_RESET,
    ];

    /**
     * All valid rule identifiers.
     *
     * @var list<string>
     */
    public const ALL_RULES = [
        self::RULE_INCREMENT,
        self::RULE_PRESERVE,
        self::RULE_RESET,
        self::RULE_OMIT,
    ];
}
