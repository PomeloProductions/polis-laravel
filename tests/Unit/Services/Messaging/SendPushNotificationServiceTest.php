<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Messaging;

use App\Models\Messaging\Message;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Kreait\Firebase\Contract\Messaging as FirebaseMessaging;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use NotificationChannels\Fcm\FcmMessage;
use Polis\Contracts\Models\Messaging\CanReceiveMessageContract;
use Polis\Contracts\Models\Messaging\CanReceivePushNotificationContract;
use Polis\Services\Messaging\SendPushNotificationService;
use Polis\Tests\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;

/**
 * Unit tests for {@see SendPushNotificationService}.
 *
 * Exercises the FCM v1 build path (Polis Message -> FcmMessage shape) and
 * the dispatch-via-multicast wiring around the Firebase Admin SDK
 * Messaging client. The Noop fallback for push_enabled=false is exercised
 * separately by BaseServiceProviderPushBindingTest.
 */
final class SendPushNotificationServiceTest extends TestCase
{
    public function test_format_push_notification_maps_data_title_and_body_to_notification_block(): void
    {
        $service = new SendPushNotificationService(
            Mockery::mock(FirebaseMessaging::class),
            Mockery::mock(LoggerInterface::class),
        );

        $message = new Message;
        $message->data = ['title' => 'Hello', 'body' => 'World', 'foo' => 'bar'];
        $message->action = 'open_inbox';

        $fcm = $service->formatPushNotification($message);

        $this->assertInstanceOf(FcmMessage::class, $fcm);
        $this->assertSame('Hello', $fcm->notification?->title);
        $this->assertSame('World', $fcm->notification?->body);
        // Data block contains the original payload (coerced to strings) plus
        // the legacy action/click_action keys carried over from the
        // benwilkins implementation.
        $this->assertIsArray($fcm->data);
        $this->assertSame('Hello', $fcm->data['title']);
        $this->assertSame('World', $fcm->data['body']);
        $this->assertSame('bar', $fcm->data['foo']);
        $this->assertSame('open_inbox', $fcm->data['action']);
        $this->assertSame('open_inbox', $fcm->data['click_action']);
    }

    public function test_format_push_notification_coerces_scalar_data_values_to_strings(): void
    {
        $service = new SendPushNotificationService(
            Mockery::mock(FirebaseMessaging::class),
            Mockery::mock(LoggerInterface::class),
        );

        $message = new Message;
        $message->data = ['count' => 42, 'enabled' => true, 'optional' => null];

        $fcm = $service->formatPushNotification($message);

        // FCM v1 only accepts string values in the data block. The service
        // coerces scalars to preserve the legacy contract; nested arrays/
        // objects are dropped silently.
        $this->assertSame('42', $fcm->data['count']);
        $this->assertSame('1', $fcm->data['enabled']);
        $this->assertSame('', $fcm->data['optional']);
    }

    public function test_send_message_returns_false_when_receiver_cannot_receive_push(): void
    {
        $service = new SendPushNotificationService(
            Mockery::mock(FirebaseMessaging::class),
            Mockery::mock(LoggerInterface::class),
        );

        // A receiver that implements the base CanReceiveMessageContract but
        // NOT CanReceivePushNotificationContract — the service must short-
        // circuit without invoking Firebase.
        $receiver = Mockery::mock(CanReceiveMessageContract::class);

        $this->assertFalse($service->sendMessage($receiver, new Message));
    }

    public function test_send_message_returns_false_when_receiver_has_no_tokens(): void
    {
        $messaging = Mockery::mock(FirebaseMessaging::class);
        $messaging->shouldNotReceive('sendMulticast');

        $service = new SendPushNotificationService(
            $messaging,
            Mockery::mock(LoggerInterface::class),
        );

        $receiver = Mockery::mock(CanReceivePushNotificationContract::class);
        $receiver->shouldReceive('canReceiveMessage')->andReturnTrue();
        $receiver->pushNotificationKeys = new EloquentCollection;

        $this->assertFalse($service->sendMessage($receiver, new Message));
    }

    public function test_send_message_dispatches_via_multicast_and_returns_true_on_any_success(): void
    {
        $report = MulticastSendReport::withItems([
            SendReport::success(MessageTarget::with(MessageTarget::TOKEN, 'device-token-1'), []),
        ]);

        $messaging = Mockery::mock(FirebaseMessaging::class);
        $messaging->shouldReceive('sendMulticast')
            ->once()
            ->withArgs(function ($fcmMessage, $tokens) {
                return $fcmMessage instanceof FcmMessage
                    && $tokens === ['device-token-1', 'device-token-2'];
            })
            ->andReturn($report);

        $service = new SendPushNotificationService(
            $messaging,
            Mockery::mock(LoggerInterface::class),
        );

        $message = new Message;
        $message->data = ['title' => 't', 'body' => 'b'];

        $receiver = $this->makeReceiverWithTokens(['device-token-1', 'device-token-2']);

        $this->assertTrue($service->sendMessage($receiver, $message));
    }

    public function test_send_message_logs_and_returns_false_on_messaging_exception(): void
    {
        $messaging = Mockery::mock(FirebaseMessaging::class);
        $messaging->shouldReceive('sendMulticast')
            ->once()
            ->andThrow(new RuntimeException('FCM down'));

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')
            ->once()
            ->withArgs(function ($msg, $ctx) {
                return str_contains($msg, 'FCM down') && isset($ctx['exception']);
            });

        // dump removed
        $service = new SendPushNotificationService($messaging, $logger);

        $message = new Message;
        $message->data = [];
        $receiver = $this->makeReceiverWithTokens(['t1']);

        $this->assertFalse($service->sendMessage($receiver, $message));
    }

    private function makeReceiverWithTokens(array $tokens): CanReceivePushNotificationContract
    {
        $keys = [];
        foreach ($tokens as $token) {
            $key = new stdClass;
            $key->push_notification_key = $token;
            $keys[] = $key;
        }

        $receiver = Mockery::mock(CanReceivePushNotificationContract::class);
        $receiver->shouldReceive('canReceiveMessage')->andReturnTrue();
        $receiver->pushNotificationKeys = new EloquentCollection($keys);

        return $receiver;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
