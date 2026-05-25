<?php

declare(strict_types=1);

namespace Polis\Services\Messaging;

use App\Models\Messaging\Message;
use Illuminate\Contracts\Mail\Mailer;
use Polis\Contracts\Models\Messaging\CanReceiveEmailsContract;
use Polis\Contracts\Models\Messaging\CanReceiveMessageContract;
use Polis\Contracts\Services\Messaging\SendEmailServiceContract;
use Polis\Mail\MessageMailer;

class SendEmailService implements SendEmailServiceContract
{
    public function __construct(private Mailer $mailer) {}

    /**
     * Attempts to send a message to the receiver
     */
    public function sendMessage(CanReceiveMessageContract $receiver, Message $message): bool
    {
        if ($receiver instanceof CanReceiveEmailsContract && $receiver->canReceiveMessage($message)) {
            $this->mailer->send(new MessageMailer($receiver, $message));

            return true;
        }

        return false;
    }
}
