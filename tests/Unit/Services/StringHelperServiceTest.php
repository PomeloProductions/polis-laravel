<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use Polis\Services\StringHelperService;
use Polis\Tests\TestCase;

/**
 * Class StringHelperServiceTest
 */
final class StringHelperServiceTest extends TestCase
{
    public function test_mb_substr_replace(): void
    {
        $service = new StringHelperService;

        $result = $service->mbSubstrReplace('你好，王', '李', 3, 1);

        $this->assertEquals('你好，李', $result);
    }

    public function test_has_domain_name()
    {
        $service = new StringHelperService;

        $this->assertTrue($service->hasDomainName('hello https://welcome.bye'));
        $this->assertFalse($service->hasDomainName('hello welcome'));
    }
}
