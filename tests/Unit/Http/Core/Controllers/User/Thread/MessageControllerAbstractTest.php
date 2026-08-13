<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\User\Thread;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Tests\Fixtures\Controllers\User\Thread\MessageController;
use Polis\Tests\Fixtures\Models\Message as MessageFixture;
use Polis\Tests\Fixtures\Models\Thread as ThreadFixture;
use Polis\Tests\Fixtures\Models\User as UserFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for User\Thread\MessageControllerAbstract.
 *
 * index() defaults order to created_at desc when no explicit order is
 * supplied. store() crafts a push-notification message payload from the
 * authenticated user and thread context. update() translates a
 * `seen: true` flag into a seen_at timestamp.
 */
final class MessageControllerAbstractTest extends ControllerTestCase
{
    public function test_index_defaults_order_to_created_at_desc_when_none_supplied(): void
    {
        $repo = Mockery::mock(MessageRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $thread = Mockery::mock(ThreadFixture::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest('Polis\\Http\\Core\\Requests\\User\\Thread\\Message\\IndexRequest');

        $repo->shouldReceive('findAll')
            ->once()
            ->with(
                [], [],
                ['created_at' => 'desc'],
                [], 10, [$thread], 1,
            )
            ->andReturn($paginator);

        $this->assertSame($paginator, (new MessageController($repo))->index($request, $user, $thread));
    }

    public function test_index_uses_supplied_order_when_present(): void
    {
        $repo = Mockery::mock(MessageRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $thread = Mockery::mock(ThreadFixture::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest(
            'Polis\\Http\\Core\\Requests\\User\\Thread\\Message\\IndexRequest',
            ['order' => ['created_at' => 'asc']],
        );

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], ['created_at' => 'asc'], [], 10, [$thread], 1)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new MessageController($repo))->index($request, $user, $thread));
    }

    public function test_store_builds_push_notification_payload(): void
    {
        $repo = Mockery::mock(MessageRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $user->id = 5;
        $user->first_name = 'Ada';

        $thread = Mockery::mock(ThreadFixture::class);
        $thread->id = 99;

        $payload = ['message' => 'Hello world'];
        $request = $this->makeRequest(
            'Polis\\Http\\Core\\Requests\\User\\Thread\\Message\\StoreRequest',
            $payload,
        );

        $created = Mockery::mock(MessageFixture::class);
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['from_id'] === 5
                    && $data['thread_id'] === 99
                    && $data['data']['body'] === 'Hello world'
                    && $data['data']['title'] === 'New message from Ada'
                    && $data['action'] === '/user/5/message'
                    && in_array('push', $data['via'], true);
            }))
            ->andReturn($created);

        $response = (new MessageController($repo))->store($request, $user, $thread);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_update_seen_flag_sets_seen_at_timestamp(): void
    {
        $repo = Mockery::mock(MessageRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $thread = Mockery::mock(ThreadFixture::class);
        $message = Mockery::mock(MessageFixture::class);
        $updated = Mockery::mock(MessageFixture::class);

        $request = $this->makeRequest(
            'Polis\\Http\\Core\\Requests\\User\\Thread\\Message\\UpdateRequest',
            ['seen' => true],
        );

        $repo->shouldReceive('update')
            ->once()
            ->with($message, Mockery::on(fn (array $d) => $d['seen_at'] instanceof Carbon))
            ->andReturn($updated);

        $this->assertSame(
            $updated,
            (new MessageController($repo))->update($request, $user, $thread, $message),
        );
    }

    public function test_update_without_seen_flag_passes_empty_data(): void
    {
        $repo = Mockery::mock(MessageRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $thread = Mockery::mock(ThreadFixture::class);
        $message = Mockery::mock(MessageFixture::class);
        $updated = Mockery::mock(MessageFixture::class);

        $request = $this->makeRequest(
            'Polis\\Http\\Core\\Requests\\User\\Thread\\Message\\UpdateRequest',
        );

        $repo->shouldReceive('update')->once()->with($message, [])->andReturn($updated);

        $this->assertSame(
            $updated,
            (new MessageController($repo))->update($request, $user, $thread, $message),
        );
    }
}
