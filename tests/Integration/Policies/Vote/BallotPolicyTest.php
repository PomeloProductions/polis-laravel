<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Vote;

use App\Models\User\User;
use App\Models\Vote\Ballot;
use App\Policies\Vote\BallotPolicy;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class BallotPolicyTest
 */
final class BallotPolicyTest extends ApplicationTestCase
{
    public function test_view(): void
    {
        $policy = new BallotPolicy;

        $this->assertTrue($policy->view(new User, new Ballot));
    }
}
