<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use Polis\Services\TokenGenerationService;
use Polis\Tests\TestCase;

/**
 * Class TokenGenerationServiceTest
 */
final class TokenGenerationServiceTest extends TestCase
{
    public function test_generate_token(): void
    {
        $service = new TokenGenerationService;

        $this->assertEquals(40, strlen($service->generateToken()));
        $this->assertEquals(54, strlen($service->generateToken(54)));
    }
}
