<?php

declare(strict_types=1);

namespace Polis\Listeners\User\UserMerge;

use Carbon\Carbon;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Events\User\UserMergeEvent;

/**
 * Class UserMergeListener
 */
class UserPropertiesMergeListener
{
    /**
     * @var UserRepositoryContract
     */
    private $userRepository;

    /**
     * UserMergeListener constructor.
     */
    public function __construct(UserRepositoryContract $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Makes sure to merge all user data properly
     */
    public function handle(UserMergeEvent $event)
    {
        $mainUser = $event->getMainUser();
        $mergeUser = $event->getMergeUser();
        $mergeOptions = $event->getMergeOptions();

        $mergeData = [];

        foreach ($mergeOptions as $field => $merge) {
            if ($merge && $mergeUser->getAttributeValue($field)) {
                $mergeData[$field] = $mergeUser->getAttributeValue($field);
            }
        }

        if ($mergeData) {
            $this->userRepository->update($mainUser, $mergeData);
        }

        $this->userRepository->update($mergeUser, [
            'merged_to_id' => $mainUser->id,
            'deleted_at' => Carbon::now(),
        ]);
    }
}
