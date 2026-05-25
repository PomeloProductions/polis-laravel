<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\ThreadSecurity;

use App\Models\Messaging\Thread;
use App\Models\User\User;
use Illuminate\Support\Collection;
use Polis\Tests\TestCase;
use Polis\ThreadSecurity\PrivateThreadGate;

/**
 * Class PrivateThreadGateTest
 */
final class PrivateThreadGateTest extends TestCase
{
    public function test_authorize_subject(): void
    {
        $gate = new PrivateThreadGate;

        $this->assertTrue($gate->authorizeSubject(new User));
    }

    public function test_authorize_thread(): void
    {
        $gate = new PrivateThreadGate;

        $thread = new Thread([
            'users' => new Collection([]),
        ]);

        $user = new User;
        $user->id = 453;

        $this->assertFalse($gate->authorizeThread($user, $thread));

        $thread->users->push($user);
        $this->assertTrue($gate->authorizeThread($user, $thread));
    }
}
