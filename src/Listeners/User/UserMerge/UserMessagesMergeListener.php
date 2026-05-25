<?php

declare(strict_types=1);

namespace Polis\Listeners\User\UserMerge;

use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\User\UserMergeEvent;

/**
 * Class UserMessagesMergeListener
 */
class UserMessagesMergeListener
{
    /**
     * @var MessageRepositoryContract
     */
    private $messageRepository;

    /**
     * UserMessagesMergeListener constructor.
     */
    public function __construct(MessageRepositoryContract $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    public function handle(UserMergeEvent $event)
    {
        $mainUser = $event->getMainUser();
        $mergeUser = $event->getMergeUser();
        $mergeOptions = $event->getMergeOptions();

        if ($mergeOptions['messages'] ?? false) {
            foreach ($mergeUser->messages as $message) {
                $this->messageRepository->update($message, [
                    'user_id' => $mainUser->id,
                ]);
            }
        }
    }
}
