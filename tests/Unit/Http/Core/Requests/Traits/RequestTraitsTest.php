<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Requests\Traits;

use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoPolicyParameters;
use Polis\Http\Core\Requests\Traits\HasNoRules;
use Polis\Tests\TestCase;
use ReflectionMethod;

/**
 * Exercises the three trivial request traits that return empty arrays
 * so the Polis-namespaced request abstracts can opt out of expands,
 * policy parameters, or validation rules.
 */
final class RequestTraitsTest extends TestCase
{
    public function test_has_no_expands_returns_empty(): void
    {
        $consumer = new class
        {
            use HasNoExpands;
        };

        $this->assertSame([], $consumer->allowedExpands());
    }

    public function test_has_no_rules_returns_empty(): void
    {
        $consumer = new class
        {
            use HasNoRules;
        };

        $this->assertSame([], $consumer->rules());
    }

    public function test_has_no_policy_parameters_returns_empty(): void
    {
        $consumer = new class
        {
            use HasNoPolicyParameters;

            // protected method - expose for the test.
            public function exposedGetPolicyParameters(): array
            {
                return $this->getPolicyParameters();
            }
        };

        $this->assertSame([], $consumer->exposedGetPolicyParameters());
    }

    public function test_has_no_policy_parameters_method_is_protected(): void
    {
        // Pin the visibility — the trait deliberately keeps getPolicyParameters()
        // protected so subclasses, not external callers, invoke it.
        $reflection = new ReflectionMethod(
            HasNoPolicyParameters::class,
            'getPolicyParameters',
        );

        $this->assertTrue($reflection->isProtected());
    }
}
