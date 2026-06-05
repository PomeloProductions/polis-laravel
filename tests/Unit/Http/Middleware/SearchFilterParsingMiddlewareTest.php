<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Middleware;

use Illuminate\Config\Repository;
use Illuminate\Http\Request;
use Polis\Exceptions\ValidationException;
use Polis\Http\Middleware\SearchFilterParsingMiddleware;
use Polis\Tests\TestCase;

/**
 * Exercises every qualifier branch in
 * SearchFilterParsingMiddleware::processQueryEntry — eq/ne/gt/lt/gte/lte,
 * in/notin, like (with leading/trailing wildcard normalization), between,
 * notnull/null, and the invalid-qualifier ValidationException path.
 */
final class SearchFilterParsingMiddlewareTest extends TestCase
{
    private function build(string $defaultDb = 'mysql'): SearchFilterParsingMiddleware
    {
        $config = new Repository(['database' => ['default' => $defaultDb]]);

        return new SearchFilterParsingMiddleware($config);
    }

    public function test_passes_through_without_filter_or_search(): void
    {
        $request = Request::create('/items', 'GET');
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertNull($request->query('cleaned_filter'));
        $this->assertNull($request->query('cleaned_search'));
    }

    public function test_parses_eq_qualifier(): void
    {
        $request = Request::create('/items', 'GET', [
            'filter' => ['status' => 'eq,active'],
        ]);
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertSame([['status', '=', 'active']], $request->query('cleaned_filter'));
    }

    public function test_parses_ne_qualifier(): void
    {
        $request = Request::create('/items', 'GET', [
            'filter' => ['status' => 'ne,active'],
        ]);
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertSame([['status', '!=', 'active']], $request->query('cleaned_filter'));
    }

    public function test_parses_comparison_qualifiers(): void
    {
        $request = Request::create('/items', 'GET', [
            'filter' => [
                'a' => 'gt,1',
                'b' => 'gte,2',
                'c' => 'lt,3',
                'd' => 'lte,4',
            ],
        ]);
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertSame([
            ['a', '>', '1'],
            ['b', '>=', '2'],
            ['c', '<', '3'],
            ['d', '<=', '4'],
        ], $request->query('cleaned_filter'));
    }

    public function test_parses_in_and_notin_qualifiers_split_csv(): void
    {
        $request = Request::create('/items', 'GET', [
            'filter' => [
                'a' => 'in,1,2,3',
                'b' => 'notin,x,y',
            ],
        ]);
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertSame([
            ['a', 'in', ['1', '2', '3']],
            ['b', 'not in', ['x', 'y']],
        ], $request->query('cleaned_filter'));
    }

    public function test_parses_like_with_leading_wildcard(): void
    {
        $request = Request::create('/items', 'GET', [
            'filter' => ['name' => 'like,*foo'],
        ]);
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertSame([['name', 'like', '%foo']], $request->query('cleaned_filter'));
    }

    public function test_parses_like_with_trailing_wildcard(): void
    {
        $request = Request::create('/items', 'GET', [
            'filter' => ['name' => 'like,foo*'],
        ]);
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertSame([['name', 'like', 'foo%']], $request->query('cleaned_filter'));
    }

    public function test_like_uses_ilike_when_pgsql(): void
    {
        $request = Request::create('/items', 'GET', [
            'filter' => ['name' => 'like,foo*'],
        ]);
        $this->build('pgsql')->handle($request, fn ($r) => $r);

        $this->assertSame([['name', 'ilike', 'foo%']], $request->query('cleaned_filter'));
    }

    public function test_between_splits_into_two_clauses(): void
    {
        $request = Request::create('/items', 'GET', [
            'filter' => ['age' => 'between,18,65'],
        ]);
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertSame([
            ['age', '>=', '18'],
            ['age', '<=', '65'],
        ], $request->query('cleaned_filter'));
    }

    public function test_notnull_qualifier(): void
    {
        $request = Request::create('/items', 'GET', [
            'filter' => ['deleted_at' => 'notnull'],
        ]);
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertSame([['deleted_at', 'IS NOT NULL', null]], $request->query('cleaned_filter'));
    }

    public function test_null_qualifier(): void
    {
        $request = Request::create('/items', 'GET', [
            'filter' => ['deleted_at' => 'null'],
        ]);
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertSame([['deleted_at', 'IS NULL', null]], $request->query('cleaned_filter'));
    }

    public function test_single_value_defaults_to_equality(): void
    {
        $request = Request::create('/items', 'GET', [
            'filter' => ['status' => 'active'],
        ]);
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertSame([['status', '=', 'active']], $request->query('cleaned_filter'));
    }

    public function test_throws_on_invalid_qualifier(): void
    {
        $request = Request::create('/items', 'GET', [
            'filter' => ['status' => 'invalid_op,foo'],
        ]);

        $this->expectException(ValidationException::class);

        $this->build()->handle($request, fn ($r) => $r);
    }

    public function test_search_param_processes_array_of_terms(): void
    {
        $request = Request::create('/items', 'GET', [
            'search' => ['name' => ['like,foo*', 'ne,bar']],
        ]);
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertSame([
            ['name', 'like', 'foo%'],
            ['name', '!=', 'bar'],
        ], $request->query('cleaned_search'));
    }

    public function test_search_param_with_single_string(): void
    {
        $request = Request::create('/items', 'GET', [
            'search' => ['name' => 'eq,bob'],
        ]);
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertSame([['name', '=', 'bob']], $request->query('cleaned_search'));
    }

    public function test_non_array_filter_is_ignored(): void
    {
        $request = Request::create('/items?filter=plain', 'GET');
        $this->build()->handle($request, fn ($r) => $r);

        $this->assertNull($request->query('cleaned_filter'));
    }
}
