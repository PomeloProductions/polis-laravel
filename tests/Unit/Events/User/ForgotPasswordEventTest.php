<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\User;

use App\Models\User\PasswordToken;
use Polis\Events\User\ForgotPasswordEvent;
use Polis\Tests\TestCase;

/**
 * Class ForgotPasswordEventTest
 */
final class ForgotPasswordEventTest extends TestCase
{
    public function test_get_password_token(): void
    {
        $passwordToken = new PasswordToken;

        $event = new ForgotPasswordEvent($passwordToken);

        $this->assertEquals($passwordToken, $event->getPasswordToken());
    }
}
