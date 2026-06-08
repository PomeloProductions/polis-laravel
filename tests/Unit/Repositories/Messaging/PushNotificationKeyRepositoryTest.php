<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Messaging;

use App\Models\Messaging\PushNotificationKey;
use Mockery;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\Messaging\PushNotificationKeyRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for PushNotificationKeyRepository — the single specialized
 * lookup (findByPushNotificationKey) and the NotImplemented traits.
 */
final class PushNotificationKeyRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! class_exists(PushNotificationKey::class, false)) {
            class_alias(
                \Polis\Models\BaseModelAbstract::class,
                PushNotificationKey::class,
            );
        }
    }

    public function test_find_by_push_notification_key_uses_where_filter(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('where')->once()->with('push_notification_key', '=', 'token-abc')->andReturnSelf();
        $expected = Mockery::mock(\Polis\Models\BaseModelAbstract::class);
        $query->shouldReceive('first')->once()->andReturn($expected);

        $modelMock = Mockery::mock(PushNotificationKey::class);
        $modelMock->shouldReceive('newQuery')->once()->andReturn($query);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $repo = new PushNotificationKeyRepository($modelMock, $this->getGenericLogMock());
        $this->assertSame($expected, $repo->findByPushNotificationKey('token-abc'));
    }

    public function test_find_all_throws_not_implemented(): void
    {
        $modelMock = Mockery::mock(PushNotificationKey::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $repo = new PushNotificationKeyRepository($modelMock, $this->getGenericLogMock());
        $this->expectException(NotImplementedException::class);
        $repo->findAll();
    }

    public function test_find_or_fail_throws_not_implemented(): void
    {
        $modelMock = Mockery::mock(PushNotificationKey::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $repo = new PushNotificationKeyRepository($modelMock, $this->getGenericLogMock());
        $this->expectException(NotImplementedException::class);
        $repo->findOrFail(1);
    }

    public function test_delete_throws_not_implemented(): void
    {
        $modelMock = Mockery::mock(PushNotificationKey::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $repo = new PushNotificationKeyRepository($modelMock, $this->getGenericLogMock());
        $this->expectException(NotImplementedException::class);
        $repo->delete(Mockery::mock(\Polis\Models\BaseModelAbstract::class));
    }
}
