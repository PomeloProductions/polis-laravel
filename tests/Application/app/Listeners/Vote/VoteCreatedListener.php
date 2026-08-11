<?php
declare(strict_types=1);

namespace App\Listeners\Vote;

use Polis\Contracts\Repositories\Vote\BallotItemOptionRepositoryContract;
use Polis\Contracts\Repositories\Vote\BallotItemRepositoryContract;
use Polis\Events\Vote\VoteCreatedEvent;

/**
 * Class VoteCreatedListener
 * @package App\Listeners\Vote
 */
class VoteCreatedListener
{
    /**
     * @var BallotItemRepositoryContract
     */
    private BallotItemRepositoryContract $ballotItemRepository;

    /**
     * @var BallotItemOptionRepositoryContract
     */
    private BallotItemOptionRepositoryContract $ballotItemOptionRepository;

    /**
     * VoteCreatedListener constructor.
     * @param BallotItemRepositoryContract $ballotItemRepository
     * @param BallotItemOptionRepositoryContract $ballotItemOptionRepository
     */
    public function __construct(BallotItemRepositoryContract $ballotItemRepository,
                                BallotItemOptionRepositoryContract $ballotItemOptionRepository)
    {
        $this->ballotItemRepository = $ballotItemRepository;
        $this->ballotItemOptionRepository = $ballotItemOptionRepository;
    }

    /**
     * Makes sure to recount the vote subject vote counts
     *
     * @param VoteCreatedEvent $event
     */
    public function handle(VoteCreatedEvent $event)
    {
        $vote = $event->getVote();

        $this->ballotItemRepository->update($vote->ballotItemOption->ballotItem, [
            'votes_cast' => $vote->ballotItemOption->ballotItem->votes_cast + 1,
        ]);

        $this->ballotItemOptionRepository->update($vote->ballotItemOption, [
            'vote_count' => $vote->ballotItemOption->vote_count + $vote->result
        ]);
    }
}
