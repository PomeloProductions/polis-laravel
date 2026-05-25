<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Vote;

use App\Models\Vote\BallotCompletion;
use Polis\Tests\TestCase;

/**
 * Class BallotCompletionTest
 */
final class BallotCompletionTest extends TestCase
{
    public function test_ballot(): void
    {
        $model = new BallotCompletion;
        $relation = $model->ballot();

        $this->assertEquals('ballots.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('ballot_completions.ballot_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_user(): void
    {
        $model = new BallotCompletion;
        $relation = $model->user();

        $this->assertEquals('users.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('ballot_completions.user_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_votes(): void
    {
        $model = new BallotCompletion;
        $relation = $model->votes();

        $this->assertEquals('ballot_completions.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('votes.ballot_completion_id', $relation->getQualifiedForeignKeyName());
    }
}
