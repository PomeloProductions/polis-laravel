<?php

declare(strict_types=1);

namespace Polis\Events\Vote;

use App\Models\Vote\Vote;

/**
 * Class VoteCreatedEvent
 */
class VoteCreatedEvent
{
    /**
     * @var Vote
     */
    private $vote;

    /**
     * VoteCreatedEvent constructor.
     */
    public function __construct(Vote $vote)
    {
        $this->vote = $vote;
    }

    public function getVote(): Vote
    {
        return $this->vote;
    }
}
