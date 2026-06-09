<?php

declare(strict_types=1);

namespace Polis\Services\Messaging;

use App\Models\Messaging\Message;
use JoliCode\Slack\ClientFactory;
use Polis\Contracts\Models\Messaging\CanReceiveMessageContract;
use Polis\Contracts\Models\Messaging\CanReceiveSlackNotificationsContract;
use Polis\Contracts\Services\Messaging\SendSlackNotificationServiceContract;

class SendSlackNotificationService implements SendSlackNotificationServiceContract
{
    /**
     * Attempts to send a message to the receiver
     */
    public function sendMessage(CanReceiveMessageContract $receiver, Message $message): bool
    {
        if ($receiver instanceof CanReceiveSlackNotificationsContract && $receiver->canReceiveMessage($message)) {

            $slackClient = ClientFactory::create($receiver->getSlackKey($message));

            $data = [
                'username' => config('polis.slack.username', config('app.name', 'Polis')),
                'channel' => $receiver->getSlackChannel($message),
                'text' => $message->subject,
            ];
            if (isset($message->data['slack_text'])) {
                $data['blocks'] = json_encode([
                    [
                        'type' => 'text',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $message->data['slack_text'],
                        ],
                    ],
                ]);
            }

            $slackClient->chatPostMessage($data);

            return true;
        }

        return false;
    }
}
