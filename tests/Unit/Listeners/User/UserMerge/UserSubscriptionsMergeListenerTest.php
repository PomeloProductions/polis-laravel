<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\User\UserMerge;

use App\Models\Subscription\Subscription;
use App\Models\User\User;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Events\User\UserMergeEvent;
use Polis\Listeners\User\UserMerge\UserSubscriptionsMergeListener;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class UserSubscriptionsMergeListenerTest
 */
final class UserSubscriptionsMergeListenerTest extends TestCase
{
    /**
     * @var SubscriptionRepositoryContract|CustomMockInterface
     */
    private $repository;

    /**
     * @var UserSubscriptionsMergeListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = mock(SubscriptionRepositoryContract::class);
        $this->listener = new UserSubscriptionsMergeListener($this->repository);
    }

    public function test_handle_without_merge(): void
    {
        $mainUser = new User([
            'email' => 'test@test.com',
        ]);
        $mainUser->id = 564534;

        $mergeUser = new User([
            'email' => 'testy@test.com',
        ]);

        $event = new UserMergeEvent($mainUser, $mergeUser);

        $this->listener->handle($event);
    }

    public function test_handle_with_merge(): void
    {
        $mainUser = new User([
            'email' => 'test@test.com',
        ]);
        $mainUser->id = 564534;

        $mergeUser = new User([
            'email' => 'testy@test.com',
            'subscriptions' => new Collection([
                new Subscription,
            ]),
        ]);

        $event = new UserMergeEvent($mainUser, $mergeUser, [
            'subscriptions' => true,
        ]);

        $this->repository->shouldReceive('update')->once()->with($mergeUser->subscriptions->first(), [
            'owner_id' => $mainUser->id,
            'payment_method_id' => null,
        ]);

        $this->listener->handle($event);
    }
}
