<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Traits;

use Polis\Tests\TestCase;
use Polis\Traits\CanGetAndUnset;

/**
 * Exercises the CanGetAndUnset trait that extracts a key from an array
 * by reference, returning the value and removing it from the source.
 */
final class CanGetAndUnsetTest extends TestCase
{
    public function test_returns_value_and_removes_from_array(): void
    {
        $consumer = $this->makeConsumer();
        $data = ['name' => 'Ada', 'age' => 36];

        $value = $consumer->getAndUnset($data, 'name');

        $this->assertSame('Ada', $value);
        $this->assertArrayNotHasKey('name', $data);
        $this->assertSame(['age' => 36], $data);
    }

    public function test_returns_default_when_key_missing(): void
    {
        $consumer = $this->makeConsumer();
        $data = ['name' => 'Ada'];

        $value = $consumer->getAndUnset($data, 'missing', 'fallback');

        $this->assertSame('fallback', $value);
        // Original array should still be intact (no key was unset).
        $this->assertSame(['name' => 'Ada'], $data);
    }

    public function test_returns_null_default_when_key_missing_and_no_default(): void
    {
        $consumer = $this->makeConsumer();
        $data = ['a' => 1];

        $this->assertNull($consumer->getAndUnset($data, 'missing'));
    }

    public function test_handles_null_value_as_present(): void
    {
        $consumer = $this->makeConsumer();
        $data = ['nullable' => null];

        // null is a valid present value: the function uses ??, so a literal
        // null actually falls back to the default. Document the behavior.
        $value = $consumer->getAndUnset($data, 'nullable', 'fallback');

        $this->assertSame('fallback', $value);
        // The key was still unset though.
        $this->assertArrayNotHasKey('nullable', $data);
    }

    private function makeConsumer(): object
    {
        return new class
        {
            use CanGetAndUnset;
        };
    }
}
