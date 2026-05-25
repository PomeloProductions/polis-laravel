<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Middleware;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use Polis\Http\Middleware\JWTGetUserFromTokenUnprotectedRouteMiddleware;
use Polis\Tests\TestCase;

/**
 * Class JWTGetUserFromTokenUnprotectedRouteMiddlewareTest
 */
final class JWTGetUserFromTokenUnprotectedRouteMiddlewareTest extends TestCase
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

        $middleware = new JWTGetUserFromTokenUnprotectedRouteMiddleware($app, $auth);

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
        $auth->shouldReceive('getToken')->once()->andReturn(false);

        $middleware = new JWTGetUserFromTokenUnprotectedRouteMiddleware($app, $auth);

        $closure = function ($param) use ($request) {
            $this->assertSame($request, $param);
        };

        $middleware->handle($request, $closure);
    }
}
