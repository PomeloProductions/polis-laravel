<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Messaging;

use App\Models\Messaging\Message;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Contracts\Models\CanReceiveTextMessagesContract;
use Polis\Events\Messaging\MessageCreatedEvent;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\Messaging\MessageRepository;
use Polis\Repositories\User\UserRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class MessageRepositoryTest
 */
class MessageRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var MessageRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new MessageRepository(
            new Message,
            $this->getGenericLogMock(),
            new UserRepository(
                new User,
                $this->getGenericLogMock(),
                mock(Hasher::class),
                $this->app->make('config'),
            ),
        );
    }

    public function test_find_all_success(): void
    {
        foreach (Message::all() as $resource) {
            $resource->delete();
        }

        Message::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        foreach (Message::all() as $resource) {
            $resource->delete();
        }

        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_create_success(): void
    {
        $user = User::factory()->create();

        $dispatcher = mock(Dispatcher::class);

        $dispatcher->shouldReceive('until');
        $dispatcher->shouldReceive('dispatch')
            ->with(\Mockery::on(function (string $eventName) {
                return true;
            }),
                \Mockery::on(function (Message $message) {
                    return true;
                })
            );
        $dispatcher->shouldReceive('dispatch')->once()
            ->with(\Mockery::on(function (MessageCreatedEvent $event) {
                return true;
            })
            );

        Message::setEventDispatcher($dispatcher);

        /** @var Message $message */
        $message = $this->repository->create([
            'subject' => 'Hello',
            'template' => 'test_template',
            'email' => 'test@test.com',
            'to_id' => $user->id,
            'to_type' => 'user',
            'data' => ['greeting' => 'hello'],
        ]);

        $this->assertEquals('Hello', $message->subject);
        $this->assertEquals('test_template', $message->template);
        $this->assertEquals('test@test.com', $message->email);
        $this->assertEquals(['greeting' => 'hello'], $message->data);
        $this->assertEquals($user->id, $message->to_id);
    }

    public function test_delete_throws_exception(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->delete(new Message);
    }

    public function test_find_or_fail_success(): void
    {
        $model = Message::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Message::factory()->create(['id' => 2]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(1);
    }

    public function test_update_success(): void
    {
        $dispatcher = mock(Dispatcher::class);
        $dispatcher->shouldReceive('until');
        $dispatcher->shouldReceive('dispatch');
        Message::setEventDispatcher($dispatcher);

        $message = Message::factory()->create();

        /** @var Message $result */
        $result = $this->repository->update($message, [
            'scheduled_at' => '2018-05-13 00:00:00',
            'sent_at' => '2018-05-13 00:00:02',
        ]);

        $this->assertEquals('2018-05-13 00:00:00', $result->scheduled_at->toDateTimeString());
        $this->assertEquals('2018-05-13 00:00:02', $result->sent_at->toDateTimeString());
    }

    public function test_send_email_to_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $dispatcher = mock(Dispatcher::class);

        $dispatcher->shouldAllowMockingMethod('fire');

        $dispatcher->shouldReceive('until');
        $dispatcher->shouldReceive('dispatch')
            ->with(\Mockery::on(function (string $eventName) {
                return true;
            }),
                \Mockery::on(function (Message $message) {
                    return true;
                })
            );
        $dispatcher->shouldReceive('dispatch')->once()
            ->with(\Mockery::on(function (MessageCreatedEvent $event) {
                return true;
            })
            );

        Message::setEventDispatcher($dispatcher);

        $result = $this->repository->sendEmailToUser($user, 'A Subject', 'template', ['yes' => 'no']);

        $this->assertEquals('A Subject', $result->subject);
        $this->assertEquals('template', $result->template);
        $this->assertEquals($user->email, $result->email);
        $this->assertEquals('no', $result->data['yes']);
        $this->assertNotNull($result->data['greeting']);
    }

    public function test_send_email_to_user_with_greeting_override(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $dispatcher = mock(Dispatcher::class);

        $dispatcher->shouldAllowMockingMethod('fire');

        $dispatcher->shouldReceive('until');
        $dispatcher->shouldReceive('dispatch')
            ->with(\Mockery::on(function (string $eventName) {
                return true;
            }),
                \Mockery::on(function (Message $message) {
                    return true;
                })
            );
        $dispatcher->shouldReceive('dispatch')->once()
            ->with(\Mockery::on(function (MessageCreatedEvent $event) {
                return true;
            })
            );

        Message::setEventDispatcher($dispatcher);

        $result = $this->repository->sendEmailToUser($user, 'A Subject', 'template', ['yes' => 'no'], 'To whom it may concern,');

        $this->assertEquals('A Subject', $result->subject);
        $this->assertEquals('template', $result->template);
        $this->assertEquals($user->email, $result->email);
        $this->assertEquals('no', $result->data['yes']);
        $this->assertNotNull($result->data['greeting']);
        $this->assertEquals('To whom it may concern,', $result->data['greeting']);
    }

    public function test_send_email_to_super_admins(): void
    {
        Message::unsetEventDispatcher();

        $user1 = User::factory()->create([
            'email' => 'test@test.com',
            'first_name' => 'System User',
        ]);
        $user1->roles()->attach(Role::SUPER_ADMIN);
        $user2 = User::factory()->create([
            'email' => 'test@test.com',
            'first_name' => 'System User',
        ]);
        $user2->roles()->attach(Role::SUPER_ADMIN);

        User::factory()->count(3)->create();

        $result = $this->repository->sendEmailToSuperAdmins('A Subject', '');

        $this->assertCount(2, $result);
        $this->assertContains($user1->id, $result->pluck('to_id'));
        $this->assertContains($user2->id, $result->pluck('to_id'));
    }

    public function test_send_text_message(): void
    {
        $textMessageReceiver = mock(CanReceiveTextMessagesContract::class);
        $textMessageReceiver->id = 234;
        $textMessageReceiver->shouldReceive('morphRelationName')->andReturn('user');
        $result = $this->repository->sendTextMessage($textMessageReceiver, 'hello');

        $this->assertEquals(['message' => 'hello'], $result->data);
    }
}
