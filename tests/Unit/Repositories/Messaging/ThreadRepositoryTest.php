<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Messaging;

use App\Models\Messaging\Thread;
use Mockery;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\Messaging\ThreadRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for ThreadRepository — the create override that syncs the
 * users pivot, and the NotImplemented update trait.
 */
final class ThreadRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! class_exists(Thread::class, false)) {
            class_alias(
                \Polis\Models\BaseModelAbstract::class,
                Thread::class,
            );
        }
    }

    public function test_create_syncs_users_after_persist(): void
    {
        $modelMock = Mockery::mock(Thread::class);
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->shouldReceive('users->sync')->once()->with([1, 2, 3]);
        $modelMock->wasRecentlyCreated = true;

        $repo = new ThreadRepository($modelMock, $this->getGenericLogMock());
        $repo->create(['subject' => 'hi', 'users' => [1, 2, 3]]);
    }

    public function test_create_syncs_empty_when_no_users_passed(): void
    {
        $modelMock = Mockery::mock(Thread::class);
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->shouldReceive('users->sync')->once()->with([]);
        $modelMock->wasRecentlyCreated = true;

        $repo = new ThreadRepository($modelMock, $this->getGenericLogMock());
        $repo->create();
    }

    public function test_update_throws_not_implemented(): void
    {
        $modelMock = Mockery::mock(Thread::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);

        $repo = new ThreadRepository($modelMock, $this->getGenericLogMock());
        $this->expectException(NotImplementedException::class);
        $repo->update(Mockery::mock(\Polis\Models\BaseModelAbstract::class), []);
    }
}
