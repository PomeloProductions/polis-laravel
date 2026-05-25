<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\ThreadSecurity;

use App\Models\Messaging\Thread;
use App\Models\User\User;
use Polis\Tests\TestCase;
use Polis\ThreadSecurity\GeneralThreadGate;

/**
 * Class GeneralThreadGateTest
 */
final class GeneralThreadGateTest extends TestCase
{
    public function test_authorize_subject(): void
    {
        $gate = new GeneralThreadGate;

        $this->assertTrue($gate->authorizeSubject(new User));
    }

    public function test_authorize_thread(): void
    {
        $gate = new GeneralThreadGate;

        $this->assertTrue($gate->authorizeThread(new User, new Thread));
    }
}
