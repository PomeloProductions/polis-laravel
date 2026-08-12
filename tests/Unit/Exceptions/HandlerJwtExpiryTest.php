<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Exceptions;

use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Polis\Exceptions\Handler;
use Polis\Tests\TestCase;

/**
 * Regression coverage for the JWT expiry / invalidity branch of
 * Handler::parseException.
 *
 * An expired or invalid JWT is an authentication failure and MUST surface as
 * a 401 — never a 500. Both PHPOpenSourceSaver TokenExpiredException and
 * TokenInvalidException extend JWTException, so they would already fall
 * through to the generic JWTException case; these tests pin the 401 contract
 * so a future reordering of the switch cannot regress an expired token to a
 * 500 (or anything else).
 */
final class HandlerJwtExpiryTest extends TestCase
{
    private function makeApiRequest(): Request
    {
        $request = new Request(server: ['REQUEST_URI' => '/v1/something']);
        $request->headers->set('Accept', 'application/json');

        return $request;
    }

    public function test_expired_token_yields_401_not_500(): void
    {
        $handler = new Handler($this->app);

        $response = $handler->render(
            $this->makeApiRequest(),
            new TokenExpiredException('Token has expired'),
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('Token has expired', $response->content());
    }

    public function test_invalid_token_yields_401_not_500(): void
    {
        $handler = new Handler($this->app);

        $response = $handler->render(
            $this->makeApiRequest(),
            new TokenInvalidException('Token is invalid'),
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('Token is invalid', $response->content());
    }

    public function test_generic_jwt_exception_still_yields_401(): void
    {
        $handler = new Handler($this->app);

        $response = $handler->render(
            $this->makeApiRequest(),
            new JWTException('Some JWT problem'),
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('Some JWT problem', $response->content());
    }
}
