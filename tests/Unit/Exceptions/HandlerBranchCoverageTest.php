<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException as IlluminateValidationException;
use Polis\Exceptions\Handler;
use Polis\Exceptions\JWT\TokenMissingException;
use Polis\Exceptions\JWT\TokenUserNotFoundException;
use Polis\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Fills in coverage for the JWT, Authorization, ValidationException
 * (Laravel built-in), and generic HttpException branches of
 * Handler::parseException — and the non-/v-prefixed pass-through.
 */
final class HandlerBranchCoverageTest extends TestCase
{
    private function makeApiRequest(): Request
    {
        $request = new Request(server: ['REQUEST_URI' => '/v1/something']);
        $request->headers->set('Accept', 'application/json');

        return $request;
    }

    public function test_token_missing_exception_uses_exception_code_as_status(): void
    {
        $handler = new Handler($this->app);
        $response = $handler->render(
            $this->makeApiRequest(),
            new TokenMissingException('missing', 400),
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_token_user_not_found_exception_uses_exception_code_as_status(): void
    {
        $handler = new Handler($this->app);
        $response = $handler->render(
            $this->makeApiRequest(),
            new TokenUserNotFoundException('gone', 401),
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_authorization_exception_yields_403_with_details(): void
    {
        $handler = new Handler($this->app);
        $response = $handler->render(
            $this->makeApiRequest(),
            new AuthorizationException('not allowed'),
        );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('not allowed', $response->content());
    }

    public function test_generic_http_exception_uses_its_status_code(): void
    {
        $handler = new Handler($this->app);
        $response = $handler->render(
            $this->makeApiRequest(),
            new HttpException(418, "I'm a teapot"),
        );

        $this->assertSame(418, $response->getStatusCode());
        $this->assertStringContainsString('teapot', $response->content());
    }

    public function test_illuminate_validation_exception_yields_400_with_errors(): void
    {
        $handler = new Handler($this->app);
        $validator = validator(['email' => 'bad'], ['email' => 'required|email']);
        $exception = new IlluminateValidationException($validator);

        $response = $handler->render($this->makeApiRequest(), $exception);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('errors', $response->content());
    }

    public function test_non_versioned_path_falls_through_to_parent_render(): void
    {
        $handler = new Handler($this->app);
        $request = new Request(server: ['REQUEST_URI' => '/some/random/path']);
        $request->headers->set('Accept', 'application/json');

        // The parent render() method will handle this. Just verify the
        // call returns a Response and doesn't throw — content shape is
        // governed by the Laravel framework.
        $response = $handler->render($request, new \RuntimeException('upstream'));

        $this->assertNotNull($response);
    }
}
