<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validators;

use Polis\Tests\Mocks\BaseValidator;
use Polis\Tests\TestCase;
use RuntimeException;

/**
 * Class BaseValidatorAbstractTest
 */
final class BaseValidatorAbstractTest extends TestCase
{
    public function test_ensure_validator_attribute_passes(): void
    {
        $validator = new BaseValidator;

        $validator->ensureValidatorAttribute('hello', 'hello');
    }

    public function test_ensure_validator_attribute_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $validator = new BaseValidator;

        $validator->ensureValidatorAttribute('hello', 'hi');
    }
}
