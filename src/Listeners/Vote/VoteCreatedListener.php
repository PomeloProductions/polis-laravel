<?php

declare(strict_types=1);

namespace Polis\Listeners\Vote;

use Polis\Contracts\Repositories\Vote\BallotItemOptionRepositoryContract;
use Polis\Contracts\Repositories\Vote\BallotItemRepositoryContract;
use Polis\Events\Vote\VoteCreatedEvent;

/**
 * Class VoteCreatedListener
 *
 * Increments the running vote-count tallies on the affected ballot item and
 * ballot-item option when a Vote is cast. Pure counter logic — no email or
 * push notifications.
 *
 * Migrated from PolisOS's app/Listeners/Vote/VoteCreatedListener.php with no
 * behavior change.
 */
class VoteCreatedListener
{
    public function __construct(
        private readonly BallotItemRepositoryContract $ballotItemRepository,
        private readonly BallotItemOptionRepositoryContract $ballotItemOptionRepository,
    ) {}

    public function handle(VoteCreatedEvent $event): void
    {
        $vote = $event->getVote();

        $this->ballotItemRepository->update($vote->ballotItemOption->ballotItem, [
            'votes_cast' => $vote->ballotItemOption->ballotItem->votes_cast + 1,
        ]);

        $this->ballotItemOptionRepository->update($vote->ballotItemOption, [
            'vote_count' => $vote->ballotItemOption->vote_count + $vote->result,
        ]);
    }
}
