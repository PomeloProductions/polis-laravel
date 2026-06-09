<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Messaging;

use App\Models\Messaging\Message;
use JoliCode\Slack\Api\Client as SlackApiClient;
use JoliCode\Slack\ClientFactory;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Polis\Contracts\Models\Messaging\CanReceiveSlackNotificationsContract;
use Polis\Services\Messaging\SendSlackNotificationService;
use Polis\Tests\TestCase;

/**
 * Tests for {@see SendSlackNotificationService}.
 *
 * The 0.2.0 release de-hardcodes the Slack `username` (previously the
 * literal string `Pomelo Productions Monitoring Bot`) and reads it from
 * `config('polis.slack.username', config('app.name'))` so each consumer
 * app appears under its own brand. This test pins that resolution path
 * so a future regression of the hardcoded string is caught at CI.
 *
 * Strategy: alias-mock JoliCode\Slack\ClientFactory so its static
 * ::create() returns a Mockery client that captures the chatPostMessage
 * payload. Assert payload['username'] reflects config.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class SendSlackNotificationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the App\Models\Messaging\Message FQN exists in the
        // autoloader before the service-under-test references it. The
        // package's Polis\Models\Messaging\Message is the real concrete;
        // the fixture autoloader provided by Polis\Tests\Fixtures aliases
        // it under App\* when not already present.
        if (! class_exists(\App\Models\Messaging\Message::class, false)) {
            class_alias(
                \Polis\Models\Messaging\Message::class,
                \App\Models\Messaging\Message::class,
            );
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_username_falls_back_to_app_name_when_polis_slack_username_key_absent(): void
    {
        // Simulate a consumer that never set POLIS_SLACK_USERNAME — the
        // merged config slot is absent and the service-under-test must
        // fall back to app.name. This is the realistic "no override" path.
        //
        // Note: Illuminate's Repository::offsetUnset on a nested dotted key
        // sets the leaf to null rather than removing it. So we rebuild the
        // repository from a copy of the existing config minus the slot.
        $all = config()->all();
        if (isset($all['polis']['slack']['username'])) {
            unset($all['polis']['slack']['username']);
        }
        $this->app->instance('config', new \Illuminate\Config\Repository($all));
        config(['app.name' => 'My Consumer App']);

        $payload = $this->captureChatPostPayload();

        $this->assertSame('My Consumer App', $payload['username']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_username_uses_polis_slack_username_when_set(): void
    {
        config([
            'polis.slack.username' => 'Custom Bot Name',
            'app.name' => 'Should Not Be Used',
        ]);

        $payload = $this->captureChatPostPayload();

        $this->assertSame('Custom Bot Name', $payload['username']);
    }

    /**
     * Drives the service-under-test and returns the payload that would
     * have been sent to chatPostMessage.
     *
     * Uses Mockery alias mocking to intercept the static
     * ClientFactory::create call. Each caller carries RunInSeparateProcess
     * because alias mocks survive only for the process lifetime and
     * pollute subsequent autoload lookups otherwise.
     */
    private function captureChatPostPayload(): array
    {
        $captured = [];

        $slackClient = Mockery::mock(SlackApiClient::class);
        $slackClient->shouldReceive('chatPostMessage')
            ->once()
            ->andReturnUsing(function (array $data) use (&$captured) {
                $captured = $data;

                return null;
            });

        $factory = Mockery::mock('alias:'.ClientFactory::class);
        $factory->shouldReceive('create')
            ->once()
            ->with('test-slack-key')
            ->andReturn($slackClient);

        $receiver = Mockery::mock(CanReceiveSlackNotificationsContract::class);
        $receiver->shouldReceive('canReceiveMessage')->andReturn(true);
        $receiver->shouldReceive('getSlackKey')->andReturn('test-slack-key');
        $receiver->shouldReceive('getSlackChannel')->andReturn('#alerts');

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getAttribute')->with('subject')->andReturn('hello');
        $message->subject = 'hello';
        $message->data = [];

        $service = new SendSlackNotificationService;
        $result = $service->sendMessage($receiver, $message);

        $this->assertTrue($result);

        return $captured;
    }
}
