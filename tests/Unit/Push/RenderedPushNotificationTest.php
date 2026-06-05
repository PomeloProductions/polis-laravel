<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Push;

use Polis\Push\RenderedPushNotification;
use Polis\Tests\TestCase;

/**
 * RenderedPushNotification is a readonly DTO; verify constructor wiring.
 */
final class RenderedPushNotificationTest extends TestCase
{
    public function test_exposes_title_and_body(): void
    {
        $rendered = new RenderedPushNotification(title: 'Hi', body: 'Hello there');

        $this->assertSame('Hi', $rendered->title);
        $this->assertSame('Hello there', $rendered->body);
    }
}
