<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Vote;

use App\Models\Vote\Vote;
use Polis\Tests\TestCase;

/**
 * Class VoteTest
 */
final class VoteTest extends TestCase
{
    public function test_ballot_completion(): void
    {
        $model = new Vote;
        $relation = $model->ballotCompletion();

        $this->assertEquals('ballot_completions.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('votes.ballot_completion_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_ballot_item_option(): void
    {
        $model = new Vote;
        $relation = $model->ballotItemOption();

        $this->assertEquals('ballot_item_options.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('votes.ballot_item_option_id', $relation->getQualifiedForeignKeyName());
    }
}
