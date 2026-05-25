<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\User;

use App\Models\User\User;
use Polis\Events\User\SignUpEvent;
use Polis\Tests\TestCase;

/**
 * Class SignUpEventTest
 */
final class SignUpEventTest extends TestCase
{
    public function test_get_user(): void
    {
        $user = new User;

        $event = new SignUpEvent($user);

        $this->assertEquals($user, $event->getUser());
    }
}
