<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Vote;

use App\Models\Vote\Ballot;
use Polis\Tests\TestCase;

/**
 * Class BallotTest
 */
final class BallotTest extends TestCase
{
    public function test_ballot_completions(): void
    {
        $model = new Ballot;
        $relation = $model->ballotCompletions();

        $this->assertEquals('ballots.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('ballot_completions.ballot_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_ballot_items(): void
    {
        $model = new Ballot;
        $relation = $model->ballotItems();

        $this->assertEquals('ballots.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('ballot_items.ballot_id', $relation->getQualifiedForeignKeyName());
    }
}
