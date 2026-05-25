<?php

declare(strict_types=1);

namespace Polis\Listeners\User\UserMerge;

use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Events\User\UserMergeEvent;

/**
 * Class UserSubscriptionsMergeListener
 */
class UserSubscriptionsMergeListener
{
    /**
     * @var SubscriptionRepositoryContract
     */
    private $subscriptionRepository;

    /**
     * UserSubscriptionsMergeListener constructor.
     */
    public function __construct(SubscriptionRepositoryContract $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * checks to merge subscriptions from a user
     */
    public function handle(UserMergeEvent $event)
    {
        $mainUser = $event->getMainUser();
        $mergeUser = $event->getMergeUser();
        $mergeOptions = $event->getMergeOptions();

        if ($mergeOptions['subscriptions'] ?? false) {
            foreach ($mergeUser->subscriptions as $subscription) {
                $this->subscriptionRepository->update($subscription, [
                    'owner_id' => $mainUser->id,
                    // the old users payment methods will not work with the new user data
                    'payment_method_id' => null,
                ]);
            }
        }
    }
}
