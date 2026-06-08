<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Polis\Http\Middleware\LogMiddleware;
use Polis\Tests\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Covers the two not-already-tested branches of LogMiddleware:
 * (1) the trivial pass-through handle() method, and
 * (2) terminate()'s testing-env short-circuit which skips logging.
 */
final class LogMiddlewareTestingEnvTest extends TestCase
{
    public function test_handle_just_passes_through(): void
    {
        $app = mock(Application::class);
        $log = mock(LoggerInterface::class);
        $middleware = new LogMiddleware($app, $log);

        $request = mock(Request::class);

        $passed = null;
        $result = $middleware->handle($request, function ($r) use (&$passed) {
            $passed = $r;

            return 'handled';
        });

        $this->assertSame($request, $passed);
        $this->assertSame('handled', $result);
    }

    public function test_terminate_skips_logging_in_testing_env(): void
    {
        $app = mock(Application::class);
        $app->shouldReceive('environment')->once()->andReturn('testing');

        // LoggerInterface methods should NOT be invoked in testing env.
        $log = mock(LoggerInterface::class);

        $middleware = new LogMiddleware($app, $log);

        $request = mock(Request::class);
        $response = mock(Response::class);

        $middleware->terminate($request, $response);

        // No assertion needed — Mockery would fail if log->info() was called.
        $this->assertTrue(true);
    }
}
