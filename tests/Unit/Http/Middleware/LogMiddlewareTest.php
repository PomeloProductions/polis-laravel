<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Polis\Http\Middleware\LogMiddleware;
use Polis\Tests\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class LogMiddlewareTest
 */
final class LogMiddlewareTest extends TestCase
{
    public function test_terminate(): void
    {
        $request = mock(Request::class);

        $request->headers = new HeaderBag(['Authorization' => 'Some Auth']);
        $request->shouldReceive('method')->once()->andReturn('GET');
        $request->shouldReceive('fullUrl')->once()->andReturn('test.com');
        $request->shouldReceive('all')->once()->andReturn('{}');
        $request->shouldReceive('ip')->once()->andReturn('129.98.19.54');

        $response = mock(Response::class);

        $response->headers = new ResponseHeaderBag([
            'Cache-Control' => 'private',
            'Date' => '2020-01-01',
        ]);
        $response->shouldReceive('getStatusCode')->once()->andReturn(200);
        $response->shouldReceive('getContent')->once()->andReturn('{}');

        $app = mock(Application::class);

        $app->shouldReceive('environment')->once()->andReturn('production');

        $log = mock(LoggerInterface::class);

        $log->shouldReceive('info')->once()->with('V1', [
            'request' => [
                'method' => 'GET',
                'url' => 'test.com',
                'data' => '{}',
                'headers' => [
                    'authorization' => ['Some Auth'],
                ],
                'ip' => '129.98.19.54',
            ],
            'response' => [
                'status' => 200,
                'headers' => [
                    'cache-control' => ['private'],
                    'date' => ['2020-01-01'],
                ],
                'content' => '{}',
            ],
        ]);

        $middleware = new LogMiddleware($app, $log);

        $middleware->terminate($request, $response);
    }
}
