<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\User;

use App\Models\User\User;
use Polis\Events\User\UserMergeEvent;
use Polis\Tests\TestCase;

/**
 * Class UserMergeEventTest
 */
final class UserMergeEventTest extends TestCase
{
    public function test_get_main_user(): void
    {
        $user = new User([
            'email' => 'something@something.something',
        ]);

        $event = new UserMergeEvent($user, new User, []);

        $this->assertEquals($user, $event->getMainUser());
    }

    public function test_get_merge_user(): void
    {
        $user = new User([
            'email' => 'something@something.something',
        ]);

        $event = new UserMergeEvent(new User, $user, []);

        $this->assertEquals($user, $event->getMergeUser());
    }

    public function test_get_merge_options(): void
    {
        $options = [
            'email' => true,
        ];

        $event = new UserMergeEvent(new User, new User, $options);

        $this->assertEquals($options, $event->getMergeOptions());
    }
}
