<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use Mockery;
use Polis\Tests\Fixtures\Models\Ballot;
use Polis\Tests\Fixtures\Policies\Vote\BallotCompletionPolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for BallotCompletionPolicyAbstract.
 *
 * - all(loggedIn, requestedUser): requires the two user-ids match.
 * - create(user, ballot): always true.
 */
final class VotePolicyAbstractTest extends TestCase
{
    public function test_ballot_completion_all_allows_self(): void
    {
        $policy = new BallotCompletionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;

        $this->assertTrue($policy->all($user, $user));
    }

    public function test_ballot_completion_all_denies_cross_user(): void
    {
        $policy = new BallotCompletionPolicy;
        $loggedIn = Mockery::mock('App\\Models\\User\\User');
        $loggedIn->id = 1;
        $requested = Mockery::mock('App\\Models\\User\\User');
        $requested->id = 2;

        $this->assertFalse($policy->all($loggedIn, $requested));
    }

    public function test_ballot_completion_create_always_returns_true(): void
    {
        $policy = new BallotCompletionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $ballot = new Ballot;

        $this->assertTrue($policy->create($user, $ballot));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
