<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use Kreait\Firebase\Contract\Messaging as FirebaseMessaging;
use Mockery;
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
 *     {@see SendPushNotificationService} wired up to the Firebase Admin
 *     SDK's Messaging client (provided by kreait/laravel-firebase, which
 *     reads service-account credentials from FIREBASE_CREDENTIALS).
 *
 * Pre-0.3.0 this binding wired up benwilkins/laravel-fcm-notification with
 * a legacy FCM server key from `app.services.fcm.key`. That library is
 * Laravel-11+ incompatible and the legacy "server key" API was sunset by
 * Google in June 2024. The new wiring goes through FCM v1 via the
 * Firebase Admin SDK service-account JSON.
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
                    $this->app->make(FirebaseMessaging::class),
                    $this->app->make('log'),
                );
            }

            return new class extends MessageSendingServiceNotImplemented implements SendPushNotificationServiceContract {};
        });

        $resolved = $this->app->make(SendPushNotificationServiceContract::class);

        $this->assertInstanceOf(MessageSendingServiceNotImplemented::class, $resolved);
    }

    public function test_enabled_branch_binds_real_service_resolving_firebase_messaging_from_container(): void
    {
        config(['polis.messaging_services.push_enabled' => true]);

        // Stub the Firebase Messaging client so we don't need real
        // credentials in the test environment. The binding under test
        // is about wiring, not about Firebase round-tripping.
        $messagingStub = Mockery::mock(FirebaseMessaging::class);
        $this->app->instance(FirebaseMessaging::class, $messagingStub);

        $this->app->bind(SendPushNotificationServiceContract::class, function () {
            if (config('polis.messaging_services.push_enabled', false)) {
                return new SendPushNotificationService(
                    $this->app->make(FirebaseMessaging::class),
                    $this->app->make('log'),
                );
            }

            return new class extends MessageSendingServiceNotImplemented implements SendPushNotificationServiceContract {};
        });

        $resolved = $this->app->make(SendPushNotificationServiceContract::class);

        $this->assertInstanceOf(SendPushNotificationService::class, $resolved);

        // Assert the constructor wired the container-resolved Messaging
        // instance into the service (vs. constructing a fresh one or
        // landing on null/a default).
        $reflection = new ReflectionClass($resolved);
        $property = $reflection->getProperty('messaging');
        $property->setAccessible(true);

        $this->assertSame($messagingStub, $property->getValue($resolved));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
