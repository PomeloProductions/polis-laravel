<?php

declare(strict_types=1);

namespace Athenia\Unit\Services\Messaging;

use App\Models\Messaging\Message;
use Polis\Contracts\Services\Messaging\SendSlackNotificationServiceContract;
use Polis\Services\Messaging\MessageSendingSelectionService;
use Polis\Tests\TestCase;

class MessageSendingSelectionServiceTest extends TestCase
{
    public function test_get_sending_service()
    {
        $slack = mock(SendSlackNotificationServiceContract::class);

        $service = new MessageSendingSelectionService([
            Message::VIA_SLACK => $slack,
        ]);

        $this->assertNull($service->getSendingService(Message::VIA_EMAIL));
        $this->assertEquals($slack, $service->getSendingService(Message::VIA_SLACK));
    }
}
