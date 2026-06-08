<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use Polis\Providers\BaseServiceProvider;
use Polis\Tests\TestCase;

/**
 * Tests for {@see BaseServiceProvider::resolveConsumerOrPackage()}.
 *
 * The helper is the cornerstone of the auto-bind behaviour: provider
 * binding sites pass it the would-be consumer FQN and the package fallback
 * FQN, and it returns whichever currently exists in the autoloader.
 *
 * These tests are intentionally narrow and Mockery-free — they only assert
 * the string-returning resolution logic, so they don't depend on the
 * fixture-model branch.
 */
final class BaseServiceProviderResolveTest extends TestCase
{
    public function test_returns_app_class_when_it_exists(): void
    {
        // Polis\Tests\TestCase itself definitely exists.
        $result = BaseServiceProvider::resolveConsumerOrPackage(
            TestCase::class,
            \stdClass::class,
        );

        $this->assertSame(TestCase::class, $result);
    }

    public function test_falls_back_to_polis_class_when_app_class_missing(): void
    {
        $result = BaseServiceProvider::resolveConsumerOrPackage(
            'App\\Definitely\\Does\\Not\\Exist\\NopeClass',
            \stdClass::class,
        );

        $this->assertSame(\stdClass::class, $result);
    }

    public function test_returns_polis_class_when_neither_exists_so_callers_see_the_polis_name_in_errors(): void
    {
        // The helper does not validate that the polis class exists either.
        // The contract is "prefer app, otherwise return polis verbatim" so a
        // misconfigured polis class surfaces as a clear class-not-found
        // error from the consuming binding site, not a silent string swap.
        $result = BaseServiceProvider::resolveConsumerOrPackage(
            'App\\Definitely\\Does\\Not\\Exist\\NopeClass',
            'Polis\\Definitely\\Does\\Not\\Exist\\NopeClass',
        );

        $this->assertSame('Polis\\Definitely\\Does\\Not\\Exist\\NopeClass', $result);
    }

    public function test_helper_is_idempotent_for_same_inputs(): void
    {
        $a = BaseServiceProvider::resolveConsumerOrPackage(
            TestCase::class,
            \stdClass::class,
        );
        $b = BaseServiceProvider::resolveConsumerOrPackage(
            TestCase::class,
            \stdClass::class,
        );

        $this->assertSame($a, $b);
    }
}
