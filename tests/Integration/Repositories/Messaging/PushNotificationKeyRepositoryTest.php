<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Messaging;

use App\Models\Messaging\PushNotificationKey;
use App\Models\User\User;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\Messaging\PushNotificationKeyRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Integration tests for PushNotificationKeyRepository.
 *
 * Covers findByPushNotificationKey (found + not-found), create, and the three
 * NotImplemented trait methods (delete / findAll / findOrFail) that must throw
 * NotImplementedException.
 */
final class PushNotificationKeyRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private PushNotificationKeyRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new PushNotificationKeyRepository(
            new PushNotificationKey,
            $this->getGenericLogMock(),
        );
    }

    // ─── findByPushNotificationKey ──────────────────────────────────────────

    public function test_find_by_push_notification_key_returns_model_when_found(): void
    {
        $user = User::factory()->create();
        $key = PushNotificationKey::factory()->create([
            'push_notification_key' => 'abc123token',
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);

        $result = $this->repository->findByPushNotificationKey('abc123token');

        $this->assertNotNull($result);
        $this->assertSame($key->id, $result->id);
        $this->assertSame('abc123token', $result->push_notification_key);
    }

    public function test_find_by_push_notification_key_returns_null_when_not_found(): void
    {
        $result = $this->repository->findByPushNotificationKey('nonexistent_key_xyz');

        $this->assertNull($result);
    }

    // ─── create ─────────────────────────────────────────────────────────────

    public function test_create_persists_push_notification_key(): void
    {
        $user = User::factory()->create();

        $key = $this->repository->create([
            'push_notification_key' => 'newdevicetoken123',
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);

        $this->assertNotNull($key->id);
        $this->assertSame('newdevicetoken123', $key->push_notification_key);
        $this->assertSame($user->id, $key->owner_id);
    }

    // ─── NotImplemented traits ──────────────────────────────────────────────

    public function test_delete_throws_not_implemented_exception(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->delete(new PushNotificationKey);
    }

    public function test_find_all_throws_not_implemented_exception(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->findAll();
    }

    public function test_find_or_fail_throws_not_implemented_exception(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->findOrFail(1);
    }
}
