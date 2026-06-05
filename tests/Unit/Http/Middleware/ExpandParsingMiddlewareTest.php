<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Middleware;

use DomainException;
use Illuminate\Http\Request;
use Polis\Http\Middleware\ExpandParsingMiddleware;
use Polis\Tests\TestCase;

/**
 * Verifies ExpandParsingMiddleware translates `expand[field]=*` query
 * parameters into a `with` query var consumed by downstream code.
 */
final class ExpandParsingMiddlewareTest extends TestCase
{
    public function test_passes_through_when_no_expand_param(): void
    {
        $middleware = new ExpandParsingMiddleware;
        $request = Request::create('/things', 'GET');

        $response = $middleware->handle($request, fn ($req) => $req);

        $this->assertSame($request, $response);
        $this->assertNull($request->query('with'));
    }

    public function test_populates_with_from_expand_array(): void
    {
        $middleware = new ExpandParsingMiddleware;
        $request = Request::create('/things', 'GET', [
            'expand' => ['author' => '*', 'comments' => '*'],
        ]);

        $middleware->handle($request, fn ($req) => $req);

        $this->assertSame(['author', 'comments'], $request->query('with'));
    }

    public function test_throws_when_columns_value_is_not_star(): void
    {
        $middleware = new ExpandParsingMiddleware;
        $request = Request::create('/things', 'GET', [
            'expand' => ['author' => 'id,name'],
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Column type of [id,name] is not yet implemented.');

        $middleware->handle($request, fn ($req) => $req);
    }

    public function test_ignores_non_array_expand_param(): void
    {
        $middleware = new ExpandParsingMiddleware;
        $request = Request::create('/things', 'GET', [
            'expand' => 'author',
        ]);

        $middleware->handle($request, fn ($req) => $req);

        $this->assertNull($request->query('with'));
    }
}
