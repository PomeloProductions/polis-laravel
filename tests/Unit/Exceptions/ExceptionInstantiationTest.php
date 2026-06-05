<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Exceptions;

use Exception;
use Polis\Exceptions\AuthenticationException;
use Polis\Exceptions\JWT\TokenMissingException;
use Polis\Exceptions\JWT\TokenUserNotFoundException;
use Polis\Exceptions\Messaging\TemplateNotFoundException;
use Polis\Exceptions\NotImplementedException;
use Polis\Exceptions\ValidationException;
use Polis\Tests\TestCase;
use RuntimeException;

/**
 * Exercises the package's exception classes — each is a trivial subclass
 * but covering them ensures the class autoloads correctly and preserves
 * its parent's message / code semantics.
 */
final class ExceptionInstantiationTest extends TestCase
{
    public function test_validation_exception_extends_exception(): void
    {
        $exception = new ValidationException('validation failed', 422);

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertSame('validation failed', $exception->getMessage());
        $this->assertSame(422, $exception->getCode());
    }

    public function test_authentication_exception_extends_runtime(): void
    {
        $exception = new AuthenticationException('auth failed', 401);

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame('auth failed', $exception->getMessage());
        $this->assertSame(401, $exception->getCode());
    }

    public function test_not_implemented_exception_extends_runtime(): void
    {
        $exception = new NotImplementedException('not yet built');

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame('not yet built', $exception->getMessage());
    }

    public function test_jwt_token_missing_exception(): void
    {
        $exception = new TokenMissingException('missing token', 400);

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertSame('missing token', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
    }

    public function test_jwt_token_user_not_found_exception(): void
    {
        $exception = new TokenUserNotFoundException('user gone', 401);

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertSame('user gone', $exception->getMessage());
        $this->assertSame(401, $exception->getCode());
    }

    public function test_template_not_found_exception(): void
    {
        $exception = new TemplateNotFoundException('no template for key x');

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame('no template for key x', $exception->getMessage());
    }
}
