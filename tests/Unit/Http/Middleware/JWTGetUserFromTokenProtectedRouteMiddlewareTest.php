<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Middleware;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use Polis\Exceptions\JWT\TokenMissingException;
use Polis\Exceptions\JWT\TokenUserNotFoundException;
use Polis\Http\Middleware\JWTGetUserFromTokenProtectedRouteMiddleware;
use Polis\Tests\TestCase;

/**
 * Class JWTGetUserFromTokenProtectedRouteMiddlewareTest
 */
final class JWTGetUserFromTokenProtectedRouteMiddlewareTest extends TestCase
{
    public function test_handle_passes_authenticate(): void
    {
        $app = mock(Application::class);

        $app->shouldReceive('environment')->once()->andReturn('production');

        $request = mock(Request::class);

        $auth = mock(JWTAuth::class);

        $auth->shouldReceive('setRequest')->once()->with($request)->andReturn($auth);
        $auth->shouldReceive('getToken')->once()->andReturn(true);
        $auth->shouldReceive('authenticate')->once()->andReturn(true);

        $middleware = new JWTGetUserFromTokenProtectedRouteMiddleware($app, $auth);

        $closure = function ($param) use ($request) {
            $this->assertSame($request, $param);
        };

        $middleware->handle($request, $closure);
    }

    public function test_handle_fails_authenticate(): void
    {
        $app = mock(Application::class);

        $app->shouldReceive('environment')->once()->andReturn('production');

        $request = mock(Request::class);

        $auth = mock(JWTAuth::class);

        $auth->shouldReceive('setRequest')->once()->with($request)->andReturn($auth);
        $auth->shouldReceive('getToken')->once()->andReturn(true);
        $auth->shouldReceive('authenticate')->once()->andReturn(false);

        $this->expectException(TokenUserNotFoundException::class);

        $middleware = new JWTGetUserFromTokenProtectedRouteMiddleware($app, $auth);

        $closure = function ($param) use ($request) {
            $this->assertSame($request, $param);
        };

        $middleware->handle($request, $closure);
    }

    public function test_handle_fails_get_token(): void
    {
        $app = mock(Application::class);

        $app->shouldReceive('environment')->once()->andReturn('production');

        $request = mock(Request::class);

        $auth = mock(JWTAuth::class);

        $auth->shouldReceive('setRequest')->once()->with($request)->andReturn($auth);
        $auth->shouldReceive('getToken')->once()->andReturn(false);

        $this->expectException(TokenMissingException::class);

        $middleware = new JWTGetUserFromTokenProtectedRouteMiddleware($app, $auth);

        $closure = function ($param) use ($request) {
            $this->assertSame($request, $param);
        };

        $middleware->handle($request, $closure);
    }
}
