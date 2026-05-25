<?php

declare(strict_types=1);

namespace Polis\Services\Messaging;

use App\Models\Messaging\Message;
use NotificationChannels\Twilio\Twilio;
use NotificationChannels\Twilio\TwilioSmsMessage;
use Polis\Contracts\Models\Messaging\CanReceiveMessageContract;
use Polis\Contracts\Models\Messaging\CanReceiveSMSContract;
use Polis\Contracts\Services\Messaging\SendSMSServiceContract;
use Psr\Log\LoggerInterface;

class SendSMSNotificationService implements SendSMSServiceContract
{
    public function __construct(private Twilio $twilio, private LoggerInterface $logger) {}

    /**
     * Attempts to send a message to the receiver
     */
    public function sendMessage(CanReceiveMessageContract $receiver, Message $message): bool
    {
        if ($receiver instanceof CanReceiveSMSContract && $receiver->canReceiveMessage($message)) {

            $sms = new TwilioSmsMessage($message->data['message']);
            try {
                $this->twilio->sendMessage($sms, $receiver->getPhoneNumber());

                return true;
            } catch (\Exception $e) {
                $error = 'Failed Sending SMS - '.$e->getMessage();
                $this->logger->error($error, $e->getTrace());

                return false;
            }
        }

        return false;
    }
}
