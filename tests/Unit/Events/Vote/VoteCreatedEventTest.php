<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\Vote;

use App\Models\Vote\Vote;
use Polis\Events\Vote\VoteCreatedEvent;
use Polis\Tests\TestCase;

/**
 * Class VoteCreatedEventTest
 */
final class VoteCreatedEventTest extends TestCase
{
    public function test_get_vote(): void
    {
        $model = new Vote;

        $event = new VoteCreatedEvent($model);

        $this->assertEquals($model, $event->getVote());
    }
}
