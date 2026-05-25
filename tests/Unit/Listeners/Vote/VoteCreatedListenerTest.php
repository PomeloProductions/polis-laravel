<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\Vote;

use App\Listeners\Vote\VoteCreatedListener;
use App\Models\Vote\BallotItem;
use App\Models\Vote\BallotItemOption;
use App\Models\Vote\Vote;
use Polis\Contracts\Repositories\Vote\BallotItemOptionRepositoryContract;
use Polis\Contracts\Repositories\Vote\BallotItemRepositoryContract;
use Polis\Events\Vote\VoteCreatedEvent;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class VoteCreatedListenerTest
 */
final class VoteCreatedListenerTest extends TestCase
{
    public function test_handle(): void
    {
        $vote = new Vote([
            'ballotItemOption' => new BallotItemOption([
                'vote_count' => 34,
                'ballotItem' => new BallotItem([
                    'votes_cast' => 45,
                ]),
            ]),
            'result' => 2,
        ]);

        $event = new VoteCreatedEvent($vote);

        /** @var BallotItemRepositoryContract|CustomMockInterface $ballotItemRepository */
        $ballotItemRepository = mock(BallotItemRepositoryContract::class);

        /** @var BallotItemOptionRepositoryContract|CustomMockInterface $ballotItemOptionRepository */
        $ballotItemOptionRepository = mock(BallotItemOptionRepositoryContract::class);

        $ballotItemRepository->shouldReceive('update')->once()->with($vote->ballotItemOption->ballotItem, [
            'votes_cast' => 46,
        ]);
        $ballotItemOptionRepository->shouldReceive('update')->once()->with($vote->ballotItemOption, [
            'vote_count' => 36,
        ]);

        $listener = new VoteCreatedListener($ballotItemRepository, $ballotItemOptionRepository);

        $listener->handle($event);
    }
}
