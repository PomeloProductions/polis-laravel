<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Polis\Http\Middleware\Issue404IfPageAfterPaginationMiddleware;
use Polis\Tests\TestCase;

/**
 * Verifies Issue404IfPageAfterPaginationMiddleware rewrites the response
 * status to 404 when the requested page exceeds the paginator's
 * last_page, and leaves valid pages alone.
 */
final class Issue404IfPageAfterPaginationMiddlewareTest extends TestCase
{
    public function test_returns_404_when_page_beyond_last_page(): void
    {
        $middleware = new Issue404IfPageAfterPaginationMiddleware;
        $request = Request::create('/things', 'GET', ['page' => 5]);

        $response = $middleware->handle($request, function () {
            return new Response(json_encode(['last_page' => 3, 'data' => []]), 200);
        });

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_keeps_200_when_page_is_within_range(): void
    {
        $middleware = new Issue404IfPageAfterPaginationMiddleware;
        $request = Request::create('/things', 'GET', ['page' => 2]);

        $response = $middleware->handle($request, function () {
            return new Response(json_encode(['last_page' => 3, 'data' => []]), 200);
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_keeps_status_when_no_page_param(): void
    {
        $middleware = new Issue404IfPageAfterPaginationMiddleware;
        $request = Request::create('/things', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response(json_encode(['last_page' => 1]), 200);
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_keeps_status_when_response_has_no_last_page(): void
    {
        $middleware = new Issue404IfPageAfterPaginationMiddleware;
        $request = Request::create('/things', 'GET', ['page' => 99]);

        $response = $middleware->handle($request, function () {
            return new Response(json_encode(['data' => []]), 200);
        });

        $this->assertSame(200, $response->getStatusCode());
    }
}
