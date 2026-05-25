<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\User;

use App\Models\User\PasswordToken;
use App\Models\User\User;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\User\ForgotPasswordEvent;
use Polis\Listeners\User\ForgotPasswordListener;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class ForgotPasswordListenerTest
 */
final class ForgotPasswordListenerTest extends TestCase
{
    public function test_handle(): void
    {
        /** @var MessageRepositoryContract|CustomMockInterface $repository */
        $repository = mock(MessageRepositoryContract::class);

        $listener = new ForgotPasswordListener($repository);

        $passwordToken = new PasswordToken([
            'user' => new User,
            'token' => 'hello',
        ]);

        $event = new ForgotPasswordEvent($passwordToken);

        $repository->shouldReceive('create')->once()->with(\Mockery::on(function ($data) {
            $this->assertArrayHasKey('subject', $data);
            $this->assertArrayHasKey('email', $data);
            $this->assertArrayHasKey('template', $data);
            $this->assertEquals('forgot-password', $data['template']);

            $this->assertArrayHasKey('data', $data);
            $this->assertArrayHasKey('greeting', $data['data']);
            $this->assertArrayHasKey('token', $data['data']);
            $this->assertEquals('hello', $data['data']['token']);

            return true;
        }), $passwordToken->user);

        $listener->handle($event);
    }
}
