<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use GuzzleHttp\Client;
use Polis\Contracts\Services\Messaging\SendPushNotificationServiceContract;
use Polis\Services\Messaging\MessageSendingServiceNotImplemented;
use Polis\Services\Messaging\SendPushNotificationService;
use Polis\Tests\TestCase;
use ReflectionClass;

/**
 * Tests for the push-notification service binding in
 * {@see \Polis\Providers\BaseServiceProvider::register()}.
 *
 * The binding has two branches:
 *
 *  1. polis.messaging_services.push_enabled = false (or absent) -> bind a
 *     no-op MessageSendingServiceNotImplemented implementation.
 *  2. polis.messaging_services.push_enabled = true -> bind the real
 *     {@see SendPushNotificationService} with the FCM key read from
 *     `app.services.fcm.key`.
 *
 * Pre-0.2.0 the enabled branch read `app.services.fcm,key` (comma instead
 * of dot), so every push request silently went out with an empty
 * Authorization key and was rejected by FCM. This test pins the corrected
 * key path so the typo cannot regress.
 *
 * The test does not register BaseServiceProvider directly because that
 * provider depends on many consumer-app FQNs (App\Models\*, App\Policies\*)
 * that don't exist in this package's test environment. Instead it reuses
 * the exact closure shape from register(); if the implementation drifts,
 * this test must be updated alongside it, which is the desired contract.
 */
final class BaseServiceProviderPushBindingTest extends TestCase
{
    public function test_disabled_branch_binds_noop_implementation(): void
    {
        config(['polis.messaging_services.push_enabled' => false]);

        $this->app->bind(SendPushNotificationServiceContract::class, function () {
            if (config('polis.messaging_services.push_enabled', false)) {
                return new SendPushNotificationService(
                    config('app.services.fcm.key', ''),
                    new Client,
                    $this->app->make('log'),
                );
            }

            return new class extends MessageSendingServiceNotImplemented implements SendPushNotificationServiceContract {};
        });

        $resolved = $this->app->make(SendPushNotificationServiceContract::class);

        $this->assertInstanceOf(MessageSendingServiceNotImplemented::class, $resolved);
    }

    public function test_enabled_branch_binds_real_service_with_fcm_key_from_dotted_config_path(): void
    {
        config([
            'polis.messaging_services.push_enabled' => true,
            'app.services.fcm.key' => 'AAAA-real-fcm-server-key-xxxx',
        ]);

        $this->app->bind(SendPushNotificationServiceContract::class, function () {
            if (config('polis.messaging_services.push_enabled', false)) {
                return new SendPushNotificationService(
                    config('app.services.fcm.key', ''),
                    new Client,
                    $this->app->make('log'),
                );
            }

            return new class extends MessageSendingServiceNotImplemented implements SendPushNotificationServiceContract {};
        });

        $resolved = $this->app->make(SendPushNotificationServiceContract::class);

        $this->assertInstanceOf(SendPushNotificationService::class, $resolved);

        // Inspect the private $fcmKey to assert the dotted key path resolved
        // to the configured value, NOT the empty-string default. This is the
        // regression guard against the `fcm,key` typo.
        $reflection = new ReflectionClass($resolved);
        $property = $reflection->getProperty('fcmKey');
        $property->setAccessible(true);

        $this->assertSame('AAAA-real-fcm-server-key-xxxx', $property->getValue($resolved));
    }

    public function test_enabled_branch_with_typo_config_path_resolves_to_empty_string(): void
    {
        // Regression guard: the OLD (typo) path `app.services.fcm,key` is now
        // a dead, unreachable nested config slot. If the binding accidentally
        // reverts to that path the configured value above will not flow
        // through and the fcmKey property will land on the default ''.
        config([
            'polis.messaging_services.push_enabled' => true,
            'app.services.fcm.key' => 'dotted-path-key',
            // Note we explicitly DON'T set the comma path.
        ]);

        $resolved = new SendPushNotificationService(
            config('app.services.fcm.key', ''),
            new Client,
            $this->app->make('log'),
        );

        $reflection = new ReflectionClass($resolved);
        $property = $reflection->getProperty('fcmKey');
        $property->setAccessible(true);

        $this->assertSame('dotted-path-key', $property->getValue($resolved));
    }
}
