<?php

declare(strict_types=1);

namespace Polis\Listeners\User\UserMerge;

use Polis\Contracts\Repositories\Vote\BallotCompletionRepositoryContract;
use Polis\Events\User\UserMergeEvent;

/**
 * Class UserBallotCompletionsMergeListener
 */
class UserBallotCompletionsMergeListener
{
    /**
     * @var BallotCompletionRepositoryContract
     */
    private $ballotCompletionRepository;

    /**
     * UserBallotCompletionsMergeListener constructor.
     */
    public function __construct(BallotCompletionRepositoryContract $ballotCompletionRepository)
    {
        $this->ballotCompletionRepository = $ballotCompletionRepository;
    }

    public function handle(UserMergeEvent $event)
    {
        $mainUser = $event->getMainUser();
        $mergeUser = $event->getMergeUser();
        $mergeOptions = $event->getMergeOptions();

        if ($mergeOptions['ballot_completions'] ?? false) {
            foreach ($mergeUser->ballotCompletions as $ballotCompletion) {
                $this->ballotCompletionRepository->update($ballotCompletion, [
                    'user_id' => $mainUser->id,
                ]);
            }
        }
    }
}
