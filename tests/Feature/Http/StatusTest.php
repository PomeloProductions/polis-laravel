<?php

/**
 * Feature test for the status controller
 */
declare(strict_types=1);

namespace Polis\Tests\Feature\Http;

use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class StatusTest
 */
final class StatusTest extends TestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplicationLog();
    }

    public function test_success(): void
    {
        $response = $this->get('/v1/status');

        $response->assertStatus(200);
        $response->assertSimilarJson([
            'status' => 'ok',
        ]);
    }
}
