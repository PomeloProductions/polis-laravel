<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Vote;

use App\Models\Vote\BallotItem;
use Polis\Tests\TestCase;

/**
 * Class BallotCompletionTest
 */
final class BallotItemTest extends TestCase
{
    public function test_ballot(): void
    {
        $model = new BallotItem;
        $relation = $model->ballot();

        $this->assertEquals('ballots.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('ballot_items.ballot_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_ballot_items(): void
    {
        $model = new BallotItem;
        $relation = $model->ballotItemOptions();

        $this->assertEquals('ballot_items.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('ballot_item_options.ballot_item_id', $relation->getQualifiedForeignKeyName());
    }
}
