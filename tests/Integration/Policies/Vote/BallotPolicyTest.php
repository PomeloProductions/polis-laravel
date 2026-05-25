<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Vote;

use App\Models\User\User;
use App\Models\Vote\Ballot;
use App\Policies\Vote\BallotPolicy;
use Polis\Tests\TestCase;

/**
 * Class BallotPolicyTest
 */
final class BallotPolicyTest extends TestCase
{
    public function test_view(): void
    {
        $policy = new BallotPolicy;

        $this->assertTrue($policy->view(new User, new Ballot));
    }
}
