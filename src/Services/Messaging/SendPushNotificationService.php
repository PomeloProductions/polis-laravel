<?php

declare(strict_types=1);

namespace Polis\Services\Messaging;

use App\Models\Messaging\Message;
use Kreait\Firebase\Contract\Messaging as FirebaseMessaging;
use Kreait\Firebase\Exception\MessagingException;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
use Polis\Contracts\Models\Messaging\CanReceiveMessageContract;
use Polis\Contracts\Models\Messaging\CanReceivePushNotificationContract;
use Polis\Contracts\Services\Messaging\SendPushNotificationServiceContract;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sends push notifications via the Firebase Cloud Messaging v1 HTTP API.
 *
 * Implementation note: this service is a thin wrapper around the
 * laravel-notification-channels/fcm package's FcmMessage value object
 * and the kreait/firebase-php Messaging client. Unlike the original
 * (benwilkins/laravel-fcm-notification) implementation, FCM v1 requires
 * a service-account JSON, not a server key — credentials are read by
 * the kreait Firebase Admin SDK from FIREBASE_CREDENTIALS (or the
 * published config/firebase.php).
 */
class SendPushNotificationService implements SendPushNotificationServiceContract
{
    public function __construct(
        private FirebaseMessaging $messaging,
        private LoggerInterface $logger,
    ) {}

    /**
     * Attempts to send a message to the receiver.
     *
     * Returns true if at least one device token accepted the push; false
     * otherwise (no recipients, network failure, or every token rejected).
     */
    public function sendMessage(CanReceiveMessageContract $receiver, Message $message): bool
    {
        if (! $receiver instanceof CanReceivePushNotificationContract || ! $receiver->canReceiveMessage($message)) {
            return false;
        }

        $tokens = $this->collectTokens($receiver);
        if ($tokens === []) {
            return false;
        }

        $fcmMessage = $this->formatPushNotification($message);

        try {
            $report = $this->messaging->sendMulticast($fcmMessage, $tokens);

            return $report->successes()->count() > 0;
        } catch (MessagingException|Throwable $e) {
            $this->logger->error(
                'Failed Sending Push Notification - '.$e->getMessage(),
                ['exception' => $e],
            );

            return false;
        }
    }

    /**
     * Build an FcmMessage from a Polis Message.
     *
     * Maps the legacy benwilkins shape to FCM v1:
     *  - $message->data['title']/['body'] -> notification block
     *  - $message->data (raw payload) -> data block (strings only)
     *  - $message->action -> data.action / data.click_action
     *
     * FCM v1 requires data-block values to be strings; non-string values
     * are coerced via (string) cast to preserve the legacy contract.
     */
    public function formatPushNotification(Message $message): FcmMessage
    {
        $payload = is_array($message->data ?? null) ? $message->data : [];

        $notification = FcmNotification::create();
        if (isset($payload['title'])) {
            $notification = $notification->title((string) $payload['title']);
        }
        if (isset($payload['body'])) {
            $notification = $notification->body((string) $payload['body']);
        }

        $data = [];
        foreach ($payload as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $data[(string) $key] = (string) ($value ?? '');
            }
        }

        if (! empty($message->action)) {
            $data['action'] = (string) $message->action;
            $data['click_action'] = (string) $message->action;
        }

        return FcmMessage::create()
            ->notification($notification)
            ->data($data);
    }

    /**
     * Pull device tokens off the receiver's pushNotificationKeys relation.
     *
     * @return list<string>
     */
    private function collectTokens(CanReceivePushNotificationContract $receiver): array
    {
        $tokens = [];
        foreach ($receiver->pushNotificationKeys as $pushNotificationKey) {
            $token = $pushNotificationKey->push_notification_key ?? null;
            if (is_string($token) && $token !== '') {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }
}
