<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Middleware;

use Polis\Http\Middleware\TrimStrings;
use Polis\Tests\TestCase;

/**
 * Verifies that the Polis TrimStrings middleware exempts the `password`
 * attribute from string trimming so leading/trailing whitespace in user
 * passwords is preserved.
 */
final class TrimStringsTest extends TestCase
{
    public function test_password_is_in_except_list(): void
    {
        $middleware = new TrimStrings;

        $except = getProperty($middleware, 'except');

        $this->assertIsArray($except);
        $this->assertContains('password', $except);
    }
}
