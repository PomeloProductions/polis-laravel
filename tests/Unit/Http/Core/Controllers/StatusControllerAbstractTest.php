<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Http\JsonResponse;
use Polis\Tests\Fixtures\Controllers\StatusController;
use Polis\Tests\TestCase;

/**
 * Unit coverage for StatusControllerAbstract.
 *
 * The controller is invokable (no DI) and just returns a static JSON
 * payload. This pins that contract.
 */
final class StatusControllerAbstractTest extends TestCase
{
    public function test_invoke_returns_status_ok_json_response(): void
    {
        $controller = new StatusController;

        $response = $controller();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['status' => 'ok'], json_decode($response->getContent(), true));
    }
}
