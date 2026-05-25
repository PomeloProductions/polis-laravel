<?php

declare(strict_types=1);

namespace Athenia\Unit\Services\Messaging;

use App\Models\Messaging\Message;
use App\Models\Organization\Organization;
use App\Models\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Polis\Services\Messaging\SendEmailService;
use Polis\Tests\TestCase;

class SendEmailServiceTest extends TestCase
{
    public function test_send_message_without_email_receiver()
    {
        $service = new SendEmailService(mock(Mailer::class));

        $service->sendMessage(new Organization, new Message);
    }

    public function test_send_message_with_email_receiver()
    {
        $mailer = mock(Mailer::class);

        $service = new SendEmailService($mailer);

        $mailer->shouldReceive('send');

        $user = new User;

        $service->sendMessage($user, new Message);
    }
}
