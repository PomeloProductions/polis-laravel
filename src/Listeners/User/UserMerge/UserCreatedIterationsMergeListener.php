<?php

declare(strict_types=1);

namespace Polis\Listeners\User\UserMerge;

use Polis\Contracts\Repositories\Wiki\ArticleIterationRepositoryContract;
use Polis\Events\User\UserMergeEvent;

/**
 * Class UserCreatedIterationsMergeListener
 */
class UserCreatedIterationsMergeListener
{
    /**
     * @var ArticleIterationRepositoryContract
     */
    private $iterationRepository;

    /**
     * UserCreatedIterationsMergeListener constructor.
     */
    public function __construct(ArticleIterationRepositoryContract $iterationRepository)
    {
        $this->iterationRepository = $iterationRepository;
    }

    public function handle(UserMergeEvent $event)
    {
        $mainUser = $event->getMainUser();
        $mergeUser = $event->getMergeUser();
        $mergeOptions = $event->getMergeOptions();

        if ($mergeOptions['created_iterations'] ?? false) {
            foreach ($mergeUser->createdIterations as $iteration) {
                $this->iterationRepository->update($iteration, [
                    'created_by_id' => $mainUser->id,
                ]);
            }
        }
    }
}
