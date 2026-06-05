<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Middleware;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use Polis\Http\Middleware\JWTGetUserFromTokenProtectedRouteMiddleware;
use Polis\Http\Middleware\JWTGetUserFromTokenUnprotectedRouteMiddleware;
use Polis\Tests\TestCase;

/**
 * Covers the "testing environment" short-circuit branch in both JWT
 * middlewares — when env == 'testing' the JWTAuth is bypassed entirely
 * and the request passes straight to the next handler.
 */
final class JWTMiddlewareTestingEnvBypassTest extends TestCase
{
    public function test_protected_route_skips_jwt_in_testing_env(): void
    {
        $app = mock(Application::class);
        $app->shouldReceive('environment')->once()->andReturn('testing');

        $request = mock(Request::class);

        // JWTAuth methods should NOT be invoked in testing env.
        $auth = mock(JWTAuth::class);

        $middleware = new JWTGetUserFromTokenProtectedRouteMiddleware($app, $auth);

        $called = false;
        $middleware->handle($request, function ($param) use (&$called, $request) {
            $this->assertSame($request, $param);
            $called = true;
        });

        $this->assertTrue($called);
    }

    public function test_unprotected_route_skips_jwt_in_testing_env(): void
    {
        $app = mock(Application::class);
        $app->shouldReceive('environment')->once()->andReturn('testing');

        $request = mock(Request::class);
        $auth = mock(JWTAuth::class);

        $middleware = new JWTGetUserFromTokenUnprotectedRouteMiddleware($app, $auth);

        $called = false;
        $middleware->handle($request, function ($param) use (&$called, $request) {
            $this->assertSame($request, $param);
            $called = true;
        });

        $this->assertTrue($called);
    }
}
