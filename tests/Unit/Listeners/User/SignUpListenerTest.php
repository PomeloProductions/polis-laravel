<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\User;

use App\Listeners\User\SignUpListener;
use App\Models\User\User;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\User\SignUpEvent;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class SignUpListenerTest
 */
final class SignUpListenerTest extends TestCase
{
    public function test_handle(): void
    {
        /** @var MessageRepositoryContract|CustomMockInterface $messageRepository */
        $repository = mock(MessageRepositoryContract::class);

        $user = new User([
            'first_name' => 'Ralph Nadar',
            'email' => 'test@test.com',
        ]);

        $repository->shouldReceive('sendEmailToUser')->once()->with(
            $user,
            'Welcome to Project Athenia!',
            'sign-up',
            [],
            'Ralph Nadar,',
        );

        $listener = new SignUpListener($repository);

        $event = new SignUpEvent($user);

        $listener->handle($event);
    }
}
