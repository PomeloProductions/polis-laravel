<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Vote;

use App\Models\User\User;
use App\Models\Vote\Ballot;
use App\Policies\Vote\BallotCompletionPolicy;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class BallotCompletionPolicyTest
 */
final class BallotCompletionPolicyTest extends ApplicationTestCase
{
    
    public function test_all(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $policy = new BallotCompletionPolicy;

        $this->assertFalse($policy->all($user1, $user2));
        $this->assertTrue($policy->all($user1, $user1));
    }

    public function test_create(): void
    {
        $policy = new BallotCompletionPolicy;

        $this->assertTrue($policy->create(new User, new Ballot));
    }
}
